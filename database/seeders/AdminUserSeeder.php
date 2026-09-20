<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('EPIC_ADMIN_EMAIL', 'admin@epic.org.pk')],
            [
                'name' => env('EPIC_ADMIN_NAME', 'EPIC Super Admin'),
                'password' => env('EPIC_ADMIN_PASSWORD', 'EpicAdmin@2025'),
                'role' => 'super_admin',
                'designation' => 'Super Administrator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
