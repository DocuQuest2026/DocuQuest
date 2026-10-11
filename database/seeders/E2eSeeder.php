<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class E2eSeeder extends Seeder
{
    /**
     * Accounts the browser and load tests sign in with. Only ever run in the isolated "e2e"
     * and "dusk" test schemas, never against real data.
     */
    public function run(): void
    {
        User::factory()->staff()->create([
            'name' => 'E2E Staff',
            'email' => 'e2e.staff@gmail.com',
            'password' => 'password',
        ]);

        User::factory()->admin()->create([
            'name' => 'E2E Admin',
            'email' => 'e2e.admin@gmail.com',
            'password' => 'password',
        ]);
    }
}
