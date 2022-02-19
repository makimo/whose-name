<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $password = 'test';

        $user = \App\Models\User::updateOrCreate(
            [
                'email' => 'test@makimo.pl'
            ], [
                'name' => 'test',
                'password' => \Hash::make($password)
            ]
        );

        $token = $user->createToken('whosename', ['whose-name']);

        $this->command->info(
            "Created user {$user->email} with password {$password} and token {$token->plainTextToken}"
        );
    }
}
