<?php

namespace Tests\Feature;

use App\Models\BillingPlan;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

class CheckClockTimeStTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $employee;
    protected $checkClockSetting;
    protected $checkClockSettingTimes;

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

        // Create check clock setting
        $this->checkClockSetting = CheckClockSetting::create([
            'company_id' => $this->company->company_id,
            'name' => 'WFA',
            'latitude' => '-6.2088',
            'longitude' => '106.8456',
            'radius' => 100
        ]);

        // Create check clock setting times
        $this->checkClockSettingTimes = CheckClockSettingTimes::create([
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

        // Authenticate as the HR user
        $this->actingAs($this->user);
    }

    /** @test */
    public function test_it_can_update_new_time_setting()
    {
        $data = [
            'minClockIn' => '08:00',
            'clockIn' => '09:30',
            'maxClockIn' => '10:30',
            'clockOut' => '18:00',
            'maxClockOut' => '19:00',
        ];

        $response = $this->putJson('/api/check-clock-setting-times/' . $this->checkClockSettingTimes->id, $data);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => ['message' => 'Successfully update the setting times'],
                 ]);

        $this->assertDatabaseHas('check_clock_setting_times', [
            'id' => $this->checkClockSettingTimes->id,
            'min_clock_in' => '08:00:00',
            'clock_in' => '09:30:00',
            'max_clock_in' => '10:30:00',
            'clock_out' => '18:00:00',
            'max_clock_out' => '19:00:00',
        ]);
    }

    /** @test */
    public function test_it_can_list_all_time_settings()
    {
        $response = $this->getJson('/api/check-clock-setting-times');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'location_rule' => [
                    '*' => [
                        'data_id',
                        'latitude',
                        'longitude',
                        'radius',
                    ],
                ],
                'ckdata' => [
                    '*' => [
                        'data_id',
                        'worktype',
                        'worktype_id',
                        'day',
                        'clock_in',
                        'min_clock_in',
                        'max_clock_in',
                        'clock_out',
                        'max_clock_out',
                        'latitude',
                        'longitude',
                        'radius',
                        'created_at',
                    ],
                ],
            ]);
    }
}
