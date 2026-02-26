<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial administrator account.
     *
     * Creates a default admin user for first-time application setup.
     * The credentials should be changed immediately after the first login
     * in any non-local environment.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@lasalle.edu.co'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin@LaSalle2026'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
