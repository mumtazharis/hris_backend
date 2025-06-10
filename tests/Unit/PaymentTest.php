<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use App\Models\Company;
use App\Models\BillingPlan;
use App\Models\CheckClockSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\EmployeeController;
use App\Models\Bills;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $billPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate
        $this->billPlan = $billingPlan = BillingPlan::create([
            'plan_name' => 'Basic Plan',
        ]);

        $this->company = Company::create([
            'name' => 'Test Company',
            'company_id' => 'COMP001',
            'plan_id' => $billingPlan->id
        ]);

        $this->user = User::create([
            'company_id' => $this->company->company_id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_profile_complete' => true,
            'auth_provider' => 'local'
        ]);

        $this->actingAs($this->user);

        // Seed a bill for the user
        Bills::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->billPlan->id,
            'plan_name' => $this->billPlan->plan_name,
            'payment_id' => 'test_payment_id',
            'total_employee' => 10,
            'amount' => 100000,
            'fine' => 5000,
            'deadline' => Carbon::now()->addDay(5)->toDateString(),
            'period' => now()->format('m-Y'),
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_create_an_invoice_and_return_invoice_url()
    {
        $this->withoutExceptionHandling();

        // Mock the Xendit API response
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'invoice_url' => 'https://xendit.co/invoice/test',
                'external_id' => 'test_payment_id',
                'amount' => 105000,
                'id' => 'xendit_test_id',
            ], 200),
        ]);

        // Call the createInvoice endpoint
        $response = $this->getJson('/api/payment');

        // Assert the response status and structure
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'invoice_url' => 'https://xendit.co/invoice/test',
            ]);
    }

    /** @test */
    public function it_can_list_bills_history_or_released()
    {
        $response = $this->getJson('/api/payment-history');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                 ])
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         '*' => [
                             'id',
                             'user_id',
                             'payment_id',
                             'amount',
                             'status',
                             'created_at',
                             'updated_at',
                         ],
                     ],
                 ]);

        // Assert the data is returned in descending order of `created_at`
        // $this->assertEquals('test_payment_id_2', $response->json('data.0.payment_id'));
        $this->assertEquals('test_payment_id', $response->json('data.0.payment_id'));
    }
}
