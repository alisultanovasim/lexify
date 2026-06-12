<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Languages
        $this->call([
            \Modules\Language\Database\Seeders\LanguageSeeder::class,
        ]);

        // Default user
        User::firstOrCreate(
            ['email' => 'asim@gmail.com'],
            [
                'name'     => 'Asim',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
            ]
        );
    }
}
