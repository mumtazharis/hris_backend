<?php

namespace Database\Factories;

use App\Models\CheckClockSetting;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'ck_setting_id' => CheckClockSetting::factory(),
            'employee_id' => strtoupper('EMP' . $this->faker->unique()->numerify('#####')),
            'nik' => $this->faker->optional()->numerify('##########'),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'position_id' => Position::factory(),
            'address' => $this->faker->optional()->address,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->unique()->optional()->phoneNumber,
            'birth_place' => $this->faker->optional()->city,
            'birth_date' => $this->faker->optional()->date('Y-m-d', '-18 years'),
            'religion' => $this->faker->optional()->randomElement(['Islam', 'Christian', 'Catholic', 'Hindu', 'Buddha', 'Konghucu']),
            'marital_status' => $this->faker->optional()->randomElement(['Single', 'Married', 'Divorced']),
            'citizenship' => $this->faker->optional()->randomElement(['WNI', 'WNA']),
            'gender' => $this->faker->optional()->randomElement(['M', 'F']),
            'blood_type' => $this->faker->optional()->randomElement(['A', 'B', 'AB', 'O']),
            'join_date' => $this->faker->optional()->date('Y-m-d', 'now'),
            'resign_date' => $this->faker->optional()->date('Y-m-d', '+5 years'),
            'employee_photo' => $this->faker->optional()->imageUrl(300, 300, 'people'),
            'employee_status' => $this->faker->optional()->randomElement(['Active', 'Inactive', 'Probation']),
        ];
    }
}