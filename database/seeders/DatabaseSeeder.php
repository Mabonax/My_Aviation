<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@myaviation.local')],
            [
                'name' => 'My Aviation Super Admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'ChangeMe!2026')),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ],
        );
    }
}
