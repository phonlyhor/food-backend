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
        User::create([
            'name' => 'Customer',
            'email' => 'phonlyhor2007@gmail.com',
            'password' => Hash::make('123123'),
            'role_id' => 2, // Customer role
            'phone_number' => '0889059604',
            'address' => 'Phnom Penh, Cambodia',
            'profile_picture' => null,
            'gender' => 'Male',
            'date_of_birth' => '2007-7-10',
        ]);
    }
}