<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_admin_can_register_with_valid_data()
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'phone' => '081234567890',
        ]);
        $this->assertArrayHasKey('token', $response->json());
    }
    
    public function test_registration_fails_without_email()
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => '',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422); // Laravel returns 422 for validation errors
        $response->assertJsonValidationErrors('email');
    }
    
    public function test_registration_fails_when_email_duplicated()
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'phone' => '081234567890',
        ]);

        $response = $this->postJson('/api/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422); // Laravel returns 422 for validation errors
        $response->assertJsonValidationErrors('email');
    }
    
    public function test_registration_fails_with_non_valid_email()
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john#example.com',
            'phone' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422); // Laravel returns 422 for validation errors
        $response->assertJsonValidationErrors('email');
    }

    public function test_user_admin_can_login_with_email()
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            // 'username' => 'john@example.com',
            'username' => '081234567890',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }
}
