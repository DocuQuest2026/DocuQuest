<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecordRequest>
 */
class RecordRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_no' => RecordRequest::generateReferenceNumber(),
            'student_no' => fake()->unique()->numerify('20##-#####'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'last_name' => fake()->lastName(),
            'course' => 'BS Information Technology',
            'enrolment_status' => EnrolmentStatus::Alumni,
            'email' => fake()->safeEmail(),
            'contact_no' => fake()->numerify('09#########'),
            'document_type' => fake()->randomElement(DocumentType::cases()),
            'copies' => 1,
            'purpose' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the requester has withdrawn the request.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
