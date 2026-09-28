<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Enums\RequestStatus;
use App\Models\DocumentRelease;
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
            'status' => RequestStatus::Pending,
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

    /**
     * Indicate that the requester has asked to cancel, awaiting staff confirmation.
     */
    public function cancellationRequested(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::CancellationRequested,
            'cancellation_requested_at' => now(),
        ]);
    }

    /**
     * Indicate that the request has been approved and is awaiting release.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::Approved,
        ]);
    }

    /**
     * Indicate that the request has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::Rejected,
        ]);
    }

    /**
     * Indicate that the request has been released to a representative.
     */
    public function released(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RequestStatus::Released,
        ])->has(DocumentRelease::factory(), 'release');
    }
}
