<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\User;

class loginTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_login_with_email_and_password()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'is_profile_complete']);
    }
    public function test_login_with_phone_and_password()
    {
        $user = User::factory()->create([
            'phone' => '081234567890',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'username' => '081234567890',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'is_profile_complete']);
    }

    // public function test_reset_password_email()
    // {
    //     $user = User::factory()->create(['email' => 'reset@test.com']);

    //     $response = $this->postJson('/api/forgot-password', [
    //         'email' => 'reset@test.com',
    //     ]);

    //     $response->assertStatus(200)
    //         ->assertJson([
    //             'message' => 'We have emailed your password reset link!'
    //         ]);
    // }





    public function test_register_new_user()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',

        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'is_profile_complete']);
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function user_can_logout_successfully()
    {
        // Membuat user dan autentikasi dengan Sanctum
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Kirim request ke endpoint logout
        $response = $this->postJson('/api/logout');
    }
    public function test_user_cannot_logout_without_authentication()
    {
        // Kirim request ke endpoint logout tanpa autentikasi
        $response = $this->postJson('/api/logout');

        // Cek responsenya
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_user_logout_invalidates_token()
    {
        // Membuat user dan autentikasi dengan Sanctum
        $response = $this->postJson('/api/register', [
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',

        ]);

        $token = $response->json('token');
        // Kirim request ke endpoint logout
        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])->postJson('/api/logout');

        // Cek responsenya
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out',
            ]);

        // // Cek bahwa token sudah dihapus
        // $this->assertDatabaseMissing('personal_access_tokens', [
        //     'tokenable_id' => $user->id,
        // ]);
    }
}
