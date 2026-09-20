<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingSeeder::class,
            MenuSeeder::class,
            PageSeeder::class,
            ContentSeeder::class,
            SampleContentSeeder::class,
        ]);
    }
}
