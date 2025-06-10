<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Employee;
use App\Models\CheckClock;
use App\Models\Position;
use App\Models\Company;
use App\Models\Department;
use App\Models\BillingPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\DashboardController;
use App\Models\CheckClockSetting;
use App\Models\CheckClockSettingTimes;
use App\Models\PresentDetail;
use App\Models\User;
use Carbon\Carbon;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private $dashboardController;
    private $company;
    private $department;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardController = new DashboardController();
        
        // Create test data hierarchy
        $this->createTestData();
    }

    private function createTestData()
    {
        // Create billing plan first
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
        $this->department = Department::create([
            'name' => 'Engineering',
            'company_id' => $this->company->company_id
        ]);

        // Create position with department
        $position = Position::create([
            'name' => 'Software Engineer',
            'department_id' => $this->department->id
        ]);

        // Create check clock setting
        $checkClockSetting = CheckClockSetting::create([
            'company_id' => $this->company->company_id,
            'name' => 'WFA',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
            'radius' => 100
        ]);

        // Create check clock setting times
        CheckClockSettingTimes::create([
            'ck_setting_id' => $checkClockSetting->id,
            'day' => 'Monday',
            'min_clock_in' => '07:00:00',
            'clock_in' => '09:00:00',
            'max_clock_in' => '10:00:00',
            'clock_out' => '17:00:00',
            'max_clock_out' => '24:00:00',
        ]);

        // Create user first
        $user = User::create([
            'company_id' => $this->company->company_id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'is_profile_complete' => true,
            'auth_provider' => 'local'
        ]);

        // Create employee with user_id and ck_setting_id
        $employee = Employee::create([
            'user_id' => $user->id,
            'company_id' => $this->company->company_id,
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $position->id,
            'employee_status' => 'Active',
            'birth_date' => Carbon::now()->subYears(25),
            'join_date' => Carbon::now()->subMonths(2),
            'contract_type' => 'Permanent',
            'gender' => 'Male',
            'marital_status' => 'Single',
            'religion' => 'Islam',
        ]);

        // Create check clock record with proper employee reference
        $checkClock = CheckClock::create([
            'employee_id' => $employee->id,
            'submitter_id' => $user->id,
            'ck_setting_id' => $checkClockSetting->id,
            'check_clock_date' => Carbon::now()->toDateString(),
            'status' => 'Present',
            'status_approval' => 'Approved'
        ]);

        PresentDetail::create([
            'ck_id' => $checkClock->id,
            'check_clock_type' => 'in', // Matches the query condition
            'check_clock_time' => '09:15:00'
        ]);
    }

    public function test_get_employee_count()
    {
        $result = $this->dashboardController->getEmployeeCount($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'Total Employee'});
        $this->assertEquals(1, $result[0]->{'Active Employee'});
    }

    public function test_get_approval_status()
    {
        $result = $this->dashboardController->getApprovalStatus($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'Approved'});
        $this->assertEquals(0, $result[0]->{'Waiting'});
    }

    public function test_get_attendance()
    {
        $result = $this->dashboardController->getAttendance($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'On Time'});
        $this->assertEquals(0, $result[0]->{'Late'});
    }

    public function test_get_employee_age()
    {
        $result = $this->dashboardController->getEmployeeAge($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'21-30'});
    }

    public function test_get_late_employee()
    {
        $result = $this->dashboardController->getLateEmployee($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('Name', (array)$result[0]);
        $this->assertArrayHasKey('Position', (array)$result[0]);
    }

    public function test_get_employee_work_status()
    {
        $result = $this->dashboardController->getEmployeeWorkStatus($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'Permanent'});
    }

    public function test_get_employee_gender()
    {
        $result = $this->dashboardController->getEmployeeGender($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'Male'});
        $this->assertEquals(0, $result[0]->{'Female'});
    }

    public function test_get_employee_marital_status()
    {
        $result = $this->dashboardController->getEmployeeMaritalStatus($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'Single'});
    }

    public function test_get_employee_religion()
    {
        $result = $this->dashboardController->getEmployeeReligion($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'Islam'});
    }

    public function test_get_employee_work_year()
    {
        $result = $this->dashboardController->getEmployeeWorkYear($this->company->company_id);
        
        $this->assertIsArray($result);
        $this->assertEquals(1, $result[0]->{'0-1'});
    }

    public function test_dashboard_returns_all_required_data()
    {
        $userAdmin = User::create([
            'company_id' => $this->company->company_id,
            'full_name' => 'Admin',
            'email' => 'admin@example.com',
            'phone' => '0877099871',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'is_profile_complete' => true,
            'auth_provider' => 'local'
        ]);

        $this->actingAs($userAdmin);

        $response = $this->dashboardController->dashboard();
        
        $this->assertEquals(200, $response->status());
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('employeeCount', $data);
        $this->assertArrayHasKey('approvalStatus', $data);
        $this->assertArrayHasKey('attendancePercentage', $data);
        $this->assertArrayHasKey('employeeAge', $data);
        $this->assertArrayHasKey('lateEmployee', $data);
        $this->assertArrayHasKey('employeeWorkStatus', $data);
        $this->assertArrayHasKey('employeeGender', $data);
        $this->assertArrayHasKey('employeeMaritalStatus', $data);
        $this->assertArrayHasKey('employeeReligion', $data);
        $this->assertArrayHasKey('employeeWorkYear', $data);
    }
}