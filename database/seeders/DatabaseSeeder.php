<?php

namespace Database\Seeders;

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
        User::factory()->superAdmin()->create([
            'name' => 'Registrar Administrator',
            'email' => 'docuquest2026@gmail.com',
        ]);

        foreach (range(1, 3) as $number) {
            User::factory()->staff()->create([
                'name' => "Registrar Staff {$number}",
                'email' => "staff{$number}@docuquest.test",
            ]);
        }
    }
}
