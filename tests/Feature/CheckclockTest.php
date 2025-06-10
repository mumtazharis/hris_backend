<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use Laravel\Sanctum\Sanctum;
use App\Models\CheckClock;
use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTimes;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\PresentDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckclockTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $position;
    protected $employee;
    protected $checkClockSetting;
    protected $checkClock;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate
        $billingPlan = BillingPlan::create([
            'plan_name' => 'Basic Plan',
        ]);

        // Create company
        $this->company = Company::create([
            'name' => 'Test Company',
            'company_id' => 'COMP001',
            'plan_id' => $billingPlan->id
        ]);
        
        // Create department
        $department = Department::create([
            'name' => 'Engineering',
            'company_id' => $this->company->company_id
        ]);

        // Create position with department
        $this->position = Position::create([
            'name' => 'Software Engineer',
            'department_id' => $department->id
        ]);

        // Create check clock setting
        $this->checkClockSetting = CheckClockSetting::create([
            'company_id' => $this->company->company_id,
            'name' => 'WFA',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
            'radius' => 100
        ]);

        // Create check clock setting times
        CheckClockSettingTimes::create([
            'ck_setting_id' => $this->checkClockSetting->id,
            'day' => 'Monday',
            'min_clock_in' => '07:00:00',
            'clock_in' => '09:00:00',
            'max_clock_in' => '10:00:00',
            'clock_out' => '17:00:00',
            'max_clock_out' => '24:00:00',
        ]);

        // Create user first
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

        // Create employee with user_id and ck_setting_id
        $this->employee = Employee::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->company_id,
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $this->position->id,
            'employee_status' => 'Active',
            'birth_date' => Carbon::now()->subYears(25),
            'join_date' => Carbon::now()->subMonths(2),
            'contract_type' => 'Permanent',
            'gender' => 'Male',
            'marital_status' => 'Single',
            'religion' => 'Islam',
        ]);

        // Create check clock record with proper employee reference
        $this->checkClock = CheckClock::create([
            'employee_id' => $this->employee->id,
            'submitter_id' => $this->user->id,
            'ck_setting_id' => $this->checkClockSetting->id,
            'check_clock_date' => Carbon::now()->addDay()->toDateString(),
            'status' => 'Present',
            'status_approval' => 'Pending',
            'reject_reason' => null
        ]);

        PresentDetail::create([
            'ck_id' => $this->checkClock->id,
            'check_clock_type' => 'in', // Matches the query condition
            'check_clock_time' => '09:15:00'
        ]);

        // Authenticate as the HR user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_check_clock()
    {
        $data = [
            'employee_id' => $this->employee->id,
            'ck_setting_name' => $this->checkClockSetting->name,
            'check_clock_date' => now()->toDateString(),
            'check_clock_time' => now()->format('H:i'),
            'check_clock_type' => 'in',
            'status' => 'Present',
        ];

        $response = $this->postJson('/api/check-clocks', $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => ['message' => 'Check clock with clock-in recorded successfully.'],
                 ]);

        $this->assertDatabaseHas('check_clocks', [
            'employee_id' => $this->employee->id,
            'status' => 'Present',
        ]);
    }

    /** @test */
    public function it_can_return_employee_that_valid_todo_check_clock()
    {
        Carbon::setTestNow(Carbon::now()->addDay());

        $response = $this->getJson('/api/cc-employee-data/');
        // dd(CheckClock::with('presentDetailCC', 'checkClockSetting')->get());
        // dd($response->json());

        $response->assertStatus(200)
        ->assertJson([[
            "data_id" => $this->employee->id,
            "id_employee"=> $this->employee->employee_id,
            "name"=> $this->employee->first_name . " " . $this->employee->last_name,
            "check_clock_date"=> $this->checkClock->check_clock_date,
            "position"=> $this->position->name,
            "worktype"=> $this->checkClockSetting->name,
            "clock_in"=> "09:15:00",
            "clock_out"=> null
        ]]);
    }

    /** @test */
    public function it_can_reject_check_clock()
    {
        $this->withoutExceptionHandling();
        
        $data = [
            'status_approval' => 'Rejected',
            'reject_reason' => 'Invalid clock-in time',
        ];
        
        $response = $this->putJson('/api/check-clock-approval/' . $this->checkClock->id, $data);
        
        $response->assertStatus(200)
        ->assertJson([
            'message' => 'Check clock status updated successfully',
        ]);

        $this->assertDatabaseHas('check_clocks', [
            'id' => $this->checkClock->id,
            'status_approval' => 'Rejected',
            'reject_reason' => 'Invalid clock-in time',
        ]);
    }
    
    /** @test */
    public function it_can_approve_check_clock()
    {
        $data = [
            'status_approval' => 'Approved',
        ];
        
        $response = $this->putJson('/api/check-clock-approval/' . $this->checkClock->id, $data);
        
        $response->assertStatus(200)
        ->assertJson([
            'message' => 'Check clock status updated successfully',
        ]);

        $this->assertDatabaseHas('check_clocks', [
            'id' => $this->checkClock->id,
            'status_approval' => 'Approved',
            'reject_reason' => null,
        ]);
    }

    /** @test */
    public function it_can_list_all_check_clocks()
    {
        $this->withoutExceptionHandling();
        // Employee::factory()->create(['company_id' => $this->user->company_id]);
        // CheckClock::factory()->count(3)->create(['status' => 'Present']);

        $response = $this->getJson('/api/check-clocks');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    /** @test */
    // public function it_can_show_a_specific_check_clock()
    // {
    //     $checkClock = CheckClock::factory()->create();

    //     $response = $this->getJson('/api/check-clocks/' . $checkClock->id);

    //     $response->assertStatus(200)
    //              ->assertJsonFragment(['id' => $checkClock->id]);
    // }
    protected function tearDown(): void
    {
        parent::tearDown();
    
        // Reset the simulated date
        Carbon::setTestNow(null);
    }
}

