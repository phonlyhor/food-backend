<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. បង្កើត Admin account (role_id = 1)
        User::firstOrCreate(
            ['email' => 'phonlyhor79@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('12345678'),
                'role_id' => 1, // Admin role
                'phone_number' => '0889059604',
                'address' => 'Phnom Penh, Cambodia',
                'profile_picture' => null,
                'gender' => 'Male',
                'date_of_birth' => '2007-07-10',
            ]
        );

        // 2. បង្កើត Customer account (role_id = 2)
        User::firstOrCreate(
            ['email' => 'phonlyhor2007@gmail.com'],
            [
                'name' => 'Customer',
                'password' => Hash::make('123123'),
                'role_id' => 2, // Customer role
                'phone_number' => '0889059604',
                'address' => 'Phnom Penh, Cambodia',
                'profile_picture' => null,
                'gender' => 'Male',
                'date_of_birth' => '2007-07-10',
            ]
        );
    }
}