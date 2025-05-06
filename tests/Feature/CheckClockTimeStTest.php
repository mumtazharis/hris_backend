<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\CheckClock;

class CheckClockTimeStTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_store_new_time_setting()
    {
        // First, create a CheckClockSetting to get the ck_setting_id
        $checkClockSetting = \App\Models\CheckClockSetting::factory()->create();

        $data = [
            'ck_setting_id' => $checkClockSetting->id,
            'day' => 'Monday',
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
        ];

        // Send a POST request to the store endpoint
        // First, create a user and log in to get the token
        $user = \App\Models\User::factory()->create();
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // Make sure the factory sets this password
        ]);

        $token = $loginResponse->json('token');

        $response = $this->postJson(
            '/api/check-clock-setting-times',
            $data,
            ['Authorization' => 'Bearer ' . $token]
        );

        // dd($token); // Debugging line to check the response

        // Assert the response status and structure
        $response->assertStatus(201);
        $response->assertJson($data);

        // Assert the data was stored in the database
        $this->assertDatabaseHas('check_clock_setting_times', $data);
    }

    public function test_it_can_update_new_time_setting()
    {
        $checkClockSetting = \App\Models\CheckClockSetting::factory()->create();
        $checkClock = \App\Models\CheckClockSettingTimes::factory()->create([
            'ck_setting_id' => $checkClockSetting->id,
            'day' => 'Monday',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        // New data for update
        $updateData = [
            'ck_setting_id' => $checkClockSetting->id,
            'day' => 'Sunday',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'break_start' => '12:00:00',
            'break_end' => '13:00:00'
        ];

        // Create a user and log in to get the token
        $user = \App\Models\User::factory()->create();
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('token');

        // Send a PUT request to the update endpoint
        $response = $this->putJson(
            '/api/check-clock-setting-times/' . $checkClock->id,
            $updateData,
            ['Authorization' => 'Bearer ' . $token]
        );

        // Assert the response status and structure
        $response->assertStatus(200);
        $response->assertJson($updateData);

        // Assert the data was updated in the database
        $this->assertDatabaseHas('check_clock_setting_times', array_merge(['id' => $checkClock->id], $updateData));
    }

    public function test_it_can_list_all_time_settings()
    {
        // Create a user and log in to get the token
        $user = \App\Models\User::factory()->create();
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('token');

        // Create multiple CheckClockSettingTimes records
        $records = \App\Models\CheckClockSettingTimes::factory()->count(3)->create();

        // Send a GET request to the index endpoint
        $response = $this->getJson(
            '/api/check-clock-setting-times',
            ['Authorization' => 'Bearer ' . $token]
        );

        $response->assertStatus(200);
        $response->assertJsonCount(3); // Assuming the endpoint returns a plain array
        // Adjust for nested "data" structure in the response
        $responseData = $response->json('data') ?? $response->json();

        $this->assertCount(3, $responseData);

        foreach ($records as $index => $record) {
            $this->assertEquals($record->id, $responseData[$index]['id']);
            $this->assertEquals($record->ck_setting_id, $responseData[$index]['ck_setting_id']);
            $this->assertEquals($record->day, $responseData[$index]['day']);
            $this->assertEquals($record->clock_in, $responseData[$index]['clock_in']);
            $this->assertEquals($record->clock_out, $responseData[$index]['clock_out']);
            $this->assertEquals($record->break_start, $responseData[$index]['break_start']);
            $this->assertEquals($record->break_end, $responseData[$index]['break_end']);
        }
    }

    public function test_it_can_show_a_single_time_setting()
    {
        // Create a user and log in to get the token
        $user = \App\Models\User::factory()->create();
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('token');

        // Create a single CheckClockSettingTimes record, ensuring it belongs to the user if necessary
        $record = \App\Models\CheckClockSettingTimes::factory()->create([
            // Add user_id if your model uses it for scoping
            // 'user_id' => $user->id,
        ]);

        // Send a GET request to the show endpoint
        $response = $this->getJson(
            '/api/check-clock-setting-times/' . $record->id,
            ['Authorization' => 'Bearer ' . $token]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $record->id,
            'ck_setting_id' => $record->ck_setting_id,
            'day' => $record->day,
            'clock_in' => $record->clock_in,
            'clock_out' => $record->clock_out,
            'break_start' => $record->break_start,
            'break_end' => $record->break_end,
        ]);
    }

    public function test_it_can_delete_a_time_setting()
    {
        // Create a user and log in to get the token
        $user = \App\Models\User::factory()->create();
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $loginResponse->json('token');

        // Create a CheckClockSettingTimes record
        $record = \App\Models\CheckClockSettingTimes::factory()->create();

        // Send a DELETE request to the destroy endpoint
        $response = $this->deleteJson(
            '/api/check-clock-setting-times/' . $record->id,
            [],
            ['Authorization' => 'Bearer ' . $token]
        );

        // Assert the response status (usually 204 No Content or 200 OK)
        $response->assertStatus(204);

        // Assert the record is deleted from the database
        $this->assertDatabaseMissing('check_clock_setting_times', [
            'id' => $record->id,
        ]);
    }
}