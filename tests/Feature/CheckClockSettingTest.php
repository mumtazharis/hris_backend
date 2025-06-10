<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CheckClockSetting;
use App\Models\Company;
use Laravel\Sanctum\Sanctum;
use App\Models\User;


class CheckClockSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $hrUser;
    protected $company;

    /** @test */
    public function test_can_update_check_clock_setting_location()
    {
        $billingPlan = BillingPlan::create([
            'plan_name' => 'Basic Plan',
        ]);

        // Create company
        $this->company = Company::create([
            'name' => 'Test Company',
            'company_id' => 'COMP001',
            'plan_id' => $billingPlan->id
        ]);
        
        // Create a user with the HR role
        $this->hrUser = User::create([
            'full_name' => 'HR Manager',
            'email' => 'hr@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'company_id' => $this->company->company_id
        ]);

        // Authenticate as the HR user
        $this->actingAs($this->hrUser);

        // Create a CheckClockSetting record
        $checkClockSetting = CheckClockSetting::create([
            'company_id' => $this->hrUser->company_id,
            'name' => 'WFO',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 100,
        ]);

        // Prepare the update data
        $updateData = [
            'data_id' => $checkClockSetting->id,
            'latitude' => -6.2000,
            'longitude' => 106.8000,
            'radius' => 150,
        ];

        // Send a PUT request to update the CheckClockSetting
        $response = $this->postJson('/api/check-clock-rule', $updateData);

        // Assert the response status and structure
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => ['message' => 'Successfully update the location'],
                     'data' => [
                         'id' => $checkClockSetting->id,
                         'latitude' => $updateData['latitude'],
                         'longitude' => $updateData['longitude'],
                         'radius' => $updateData['radius'],
                     ],
                 ]);

        // Assert the database has the updated values
        $this->assertDatabaseHas('check_clock_settings', [
            'id' => $checkClockSetting->id,
            'latitude' => $updateData['latitude'],
            'longitude' => $updateData['longitude'],
            'radius' => $updateData['radius'],
        ]);
    }
}
