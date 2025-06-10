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

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $hrUser;
    protected $company;
    protected $department;
    protected $position;
    protected $controller;
    protected $checkClockSetting;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data hierarchy
        $this->createBaseData();
        
        // Initialize controller
        $this->controller = new EmployeeController();
    }

    private function createBaseData()
    {
        // Create billing plan
        $billingPlan = BillingPlan::create([
            'plan_name' => 'Basic Plan',
        ]);

        // Create company
        $this->company = Company::create([
            'name' => 'Test Company',
            'company_id' => 'COMP001',
            'plan_id' => $billingPlan->id
        ]);

        // Create HR user
        $this->hrUser = User::create([
            'full_name' => 'HR Manager',
            'email' => 'hr@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'company_id' => $this->company->company_id
        ]);

        // Create department
        $this->department = Department::create([
            'name' => 'Engineering',
            'company_id' => $this->company->company_id
        ]);

        // Create position
        $this->position = Position::create([
            'name' => 'Software Engineer',
            'department_id' => $this->department->id,
            // 'salary' => 5000.00
        ]);

        // Create check clock setting
        $this->checkClockSetting = CheckClockSetting::create([
            'name' => 'WFA',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
            'radius' => 100
        ]);
    }

    public function test_can_list_employees()
    {
        $this->actingAs($this->hrUser);

        $employee = Employee::create([
            'user_id' => $this->hrUser->id,
            'employee_id' => '23000001',
            'company_id' => $this->company->company_id,
            'nik' => '1234567890123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $this->position->id,
            'address' => 'Jl. Test Address No. 123',
            'email' => 'john.doe@test.com',
            'phone' => '6281234567890',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'education' => 'SD',
            'religion' => 'Islam',
            'marital_status' => 'Single',
            'citizenship' => 'Indonesia',
            'gender' => 'Male',
            'blood_type' => 'O',
            'salary' => 5000000,
            'contract_type' => 'Permanent',
            'contract_end' => null,  // null for permanent employees
            'join_date' => "2023-01-01",
            'exit_date' => null,
            'employee_photo' => null,
            'employee_status' => 'Active'
        ]);

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'periode',
                    'summary' => [
                        'Total Employee',
                        'Total New Hire',
                        'Active Employee'
                    ],
                    'employees'
                ]);
    }

    public function test_can_create_employee()
    {
        $this->actingAs($this->hrUser);

        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'phone' => '6281234567890',
            'nik' => '1234567890123456',
            'birth_place' => 'Jakarta',
            'birth_date' => '1990-01-01',
            'education' => 'SD',
            'position_id' => $this->position->id,
            'gender' => 'Male',
            'marital_status' => 'Single',
            'citizenship' => 'Indonesia',
            'address' => 'Jalan Simpang Merdeka',
            'blood_type' => 'O',
            'religion' => 'Islam',
            'join_date' => now(), 
            'contract_type' => 'Permanent',
            'employee_status' => 'Active'
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'employee' => [
                        'id',
                        'employee_id',
                        'first_name',
                        'last_name'
                    ]
                ]);
    }

    public function test_can_update_employee()
    {
        $this->actingAs($this->hrUser);

        $employee = Employee::create([
            'user_id' => $this->hrUser->id,
            'company_id' => $this->company->company_id,
            'employee_id' => '23000001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $this->position->id,
            'join_date' => now(),  // Add this line
            'employee_status' => 'Active'
        ]);

        $updateData = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '6281234567890'
        ];

        $response = $this->patchJson("/api/employees/{$employee->employee_id}", $updateData);

        $response->assertStatus(200)
                ->assertJson([
                    'first_name' => 'Jane'
                ]);
    }

    public function test_can_delete_employee()
    {
        $employeeUser = User::create([
            'full_name' => 'Employee User',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
            'company_id' => $this->company->company_id,
        ]);

        $this->actingAs($this->hrUser);

        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'company_id' => $employeeUser->company->company_id,
            'employee_id' => '23000001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $this->position->id,
            'join_date' => now(),  // Add this line
            'employee_status' => 'Active'
        ]);

        $response = $this->deleteJson("/api/employees/{$employee->employee_id}");

        $response->assertStatus(200)
                ->assertJson(['message' => 'Employee deleted successfully']);
    }

    public function test_can_export_csv()
    {
        $this->actingAs($this->hrUser);

        $employee = Employee::create([
            'user_id' => $this->hrUser->id,
            'company_id' => $this->company->company_id,
            'employee_id' => '23000001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $this->position->id,
            'join_date' => now(),  // Add this line
            'employee_status' => 'Active'
        ]);

        $response = $this->get('/api/employee/export-csv');

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
                ->assertHeader('Content-Disposition', 'attachment; filename="employee.csv"');
    }

    public function test_can_preview_csv_import()
    {
        $this->actingAs($this->hrUser);

        Storage::fake('local');

       $csvContent = "first_name,last_name,email,phone,nik,position_id,birth_date,contract_type,join_date,education,gender,blood_type,marital_status,employee_status\n" .
                  "John,Doe,john@test.com,6281234567890,1234567890123456,{$this->position->id},1990-01-01,Permanent,2023-01-01,S1,Male,O,Single,Active";
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->postJson('/api/employees/preview-csv', [
            'csv_file' => $file
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_rows',
                    'valid_rows_count',
                    'invalid_rows_count',
                    'valid_rows',
                    'invalid_rows'
                ]);
    }

    public function test_can_reset_employee_password()
    {
        $this->actingAs($this->hrUser);

        $employee = Employee::create([
            'user_id' => $this->hrUser->id,
            'company_id' => $this->company->company_id,
            'employee_id' => '23000001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'position_id' => $this->position->id,
            'join_date' => now(),  // Add this line
            'employee_status' => 'Active'
        ]);

        $response = $this->postJson("/api/employees/{$employee->employee_id}/reset-password");

        $response->assertStatus(200)
                ->assertJson([
                     "message" => "Password has been successfully reset to the default.",
                     "default_password"=> "23000001"
                ]);
    }
}