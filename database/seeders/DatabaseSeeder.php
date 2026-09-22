<?php

namespace Database\Seeders;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with local development accounts.
     *
     * Every account uses the factory's default password ("password"); never run this against production.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Registrar Administrator',
            'email' => 'admin@docuquest.test',
        ]);

        foreach (range(1, 3) as $number) {
            User::factory()->staff()->create([
                'name' => "Registrar Staff {$number}",
                'email' => "staff{$number}@docuquest.test",
            ]);
        }

        $student = User::factory()->create([
            'name' => 'Jake Estera',
            'email' => 'student@docuquest.test',
        ]);

        StudentProfile::factory()->for($student)->create([
            'student_no' => '2022-00001',
            'first_name' => 'Jake',
            'middle_name' => null,
            'last_name' => 'Estera',
            'course' => 'BS Information Technology',
            'year_level' => 4,
        ]);
    }
}
