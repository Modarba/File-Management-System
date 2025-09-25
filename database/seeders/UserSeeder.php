<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user=User::create(['name'=>'user','email'=>'user@user.com','password'=>Hash::make('password')]
        );
        $token=$user->createToken('token')->plainTextToken;
        $this->command->info($token);
    }
}
