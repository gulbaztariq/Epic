<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Read through config rather than env() so the values are still
        // available when the configuration has been cached for production.
        User::updateOrCreate(
            ['email' => config('epic.admin.email')],
            [
                'name' => config('epic.admin.name'),
                'password' => config('epic.admin.password'),
                'role' => 'super_admin',
                'designation' => 'Super Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
