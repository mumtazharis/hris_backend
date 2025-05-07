<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use App\Models\CheckClockSetting;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeFactory extends Factory
{
    use HasFactory;
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // otomatis buat user
            'ck_setting_id' => CheckClockSetting::factory(), // otomatis buat setting
            'employee_id' => $this->faker->unique()->randomNumber(5),
            'nik' => $this->faker->unique()->numerify('3204###########'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'position_id' => Position::factory(), // otomatis buat position
            'address' => $this->faker->address(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->date('Y-m-d'),
            'religion' => $this->faker->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'blood_type' => $this->faker->randomElement(['A', 'B', 'AB', 'O']),
            'join_date' => $this->faker->date('Y-m-d'),
            'resign_date' => null,
            'employee_photo' => 'default.jpg',
            'employee_status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
