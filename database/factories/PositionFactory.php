<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition()
    {
        return [
            'name' => $this->faker->jobTitle,
            'department_id' => Department::factory(),
            'salary' => $this->faker->randomFloat(2, 3000, 20000),
        ];
    }
}
