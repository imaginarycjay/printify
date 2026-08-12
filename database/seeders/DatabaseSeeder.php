<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'owner@capstone.test'],
            [
                'name' => 'Business Owner',
                'password' => 'password',
                'role' => User::ROLE_BUSINESS_OWNER,
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@capstone.test'],
            [
                'name' => 'Production Staff',
                'password' => 'password',
                'role' => User::ROLE_PRODUCTION_STAFF,
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer@capstone.test'],
            [
                'name' => 'Customer User',
                'password' => 'password',
                'role' => User::ROLE_CUSTOMER,
            ]
        );
    }
}
