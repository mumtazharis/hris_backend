<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PositionFactory extends Factory
{
    use HasFactory;
    protected $model = Position::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->jobTitle(),
            'department_id' => Department::factory(), // otomatis buat department baru
            'salary' => $this->faker->randomFloat(2, 3000, 10000),
        ];
    }
}
