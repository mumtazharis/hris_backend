<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\CheckClockSetting;
use Laravel\Sanctum\Sanctum;
use App\Models\User;


class CheckClockSettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_settings()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    
        $token = $response->json('token');
    
        CheckClockSetting::factory()->count(3)->create();
    
       
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token, 
        ])->getJson('/api/check-clock-settings'); 
    
        $response->assertStatus(200)->assertJsonCount(3);
    }
    

    /** @test */
    public function it_can_store_a_new_setting()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    
        $token = $response->json('token');
    
        $data = [
            'name' => 'WFO',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'radius' => '100',
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token,])->postJson('/api/check-clock-settings', $data);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'WFO']);

        $this->assertDatabaseHas('check_clock_settings', ['name' => 'WFO']);
    }

    /** @test */
    public function it_can_show_a_setting()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    
        $token = $response->json('token');
    
        $setting = CheckClockSetting::factory()->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token,])->getJson("/api/check-clock-settings/{$setting->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $setting->id]);
    }

    /** @test */
    public function it_can_update_a_setting()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    
        $token = $response->json('token');

        $setting = CheckClockSetting::factory()->create();

        $updatedData = [
            'name' => 'WFO2',
            'latitude' => (string) $setting->latitude,
            'longitude' => (string) $setting->longitude,
            'radius' => $setting->radius,
        ];

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token,])->putJson("/api/check-clock-settings/{$setting->id}", $updatedData);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'WFO2']);
    }

    /** @test */
    public function it_can_delete_a_setting()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
    
        $token = $response->json('token');

        $setting = CheckClockSetting::factory()->create();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token,])->deleteJson("/api/check-clock-settings/{$setting->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Resource deleted successfully']);

        $this->assertDatabaseMissing('check_clock_settings', ['id' => $setting->id]);
    }
}
