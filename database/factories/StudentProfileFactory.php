<?php

namespace Database\Factories;

use App\Enums\EnrolmentStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_no' => fake()->unique()->numerify('20##-#####'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->lastName(),
            'course' => fake()->randomElement([
                'BS Information Technology',
                'BS Computer Science',
                'BS Business Administration',
                'BS Education',
            ]),
            'year_level' => fake()->numberBetween(1, 4),
            'contact_no' => fake()->numerify('09#########'),
            'enrolment_status' => EnrolmentStatus::Enrolled,
        ];
    }

    /**
     * Indicate that the student has graduated and therefore has no year level.
     */
    public function graduated(): static
    {
        return $this->state(fn (array $attributes) => [
            'year_level' => null,
            'enrolment_status' => EnrolmentStatus::Graduated,
        ]);
    }
}
