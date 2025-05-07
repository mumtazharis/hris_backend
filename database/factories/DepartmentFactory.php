<?php
namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DepartmentFactory extends Factory
{
    use HasFactory;
    protected $model = Department::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word() . ' Department',
            'description' => $this->faker->sentence(),
        ];
    }
}
