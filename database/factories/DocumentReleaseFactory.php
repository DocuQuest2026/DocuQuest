<?php

namespace Database\Factories;

use App\Models\DocumentRelease;
use App\Models\RecordRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentRelease>
 */
class DocumentReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'record_request_id' => RecordRequest::factory(),
            'released_by' => User::factory()->staff(),
            'released_at' => now(),
            'representative_name' => fake()->name(),
            'verification_token' => DocumentRelease::generateVerificationToken(),
        ];
    }
}
