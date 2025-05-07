<?php

namespace Tests\Feature;

use Laravel\Sanctum\Sanctum;
use App\Models\CheckClock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckclockTest extends TestCase
{
    protected $user;

    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => bcrypt('password')
        ]);

        Sanctum::actingAs($this->user, ['*']);
    }

    /** @test */
    public function it_can_create_check_clock()
    {
        $employee = \App\Models\Employee::factory()->create();
        $data = [
            'employee_id' => $employee->id,
            'check_clock_type' => 'in',
            'check_clock_date' => now()->toDateString(),
            'check_clock_time' => now()->format('H:i:s'),
            'status' => 'pending',
        ];

        $response = $this->postJson('/api/check-clocks', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('check_clocks', $data);
    }

    /** @test */
    public function it_can_list_all_check_clocks()
    {
        \App\Models\Employee::factory()->create();
        CheckClock::factory()->count(3)->create();

        $response = $this->getJson('/api/check-clocks');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    /** @test */
    public function it_can_show_a_specific_check_clock()
    {
        $checkClock = CheckClock::factory()->create();

        $response = $this->getJson('/api/check-clocks/' . $checkClock->id);

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $checkClock->id]);
    }

    /** @test */
    public function it_can_update_a_check_clock()
    {
        $checkClock = CheckClock::factory()->create();

        $data = ['status' => 'approved'];

        $response = $this->putJson('/api/check-clocks/' . $checkClock->id, $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('check_clocks', ['id' => $checkClock->id, 'status' => 'approved']);
    }
}
