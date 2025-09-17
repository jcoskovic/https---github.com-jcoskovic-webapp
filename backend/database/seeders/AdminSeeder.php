<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Stvori admin korisnika
        User::create([
            'name' => 'Super Admin',
            'email' => 'super.admin@abbrevio.com',
            'password' => bcrypt('admin1234'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Možeš dodati više admin korisnika
        User::create([
            'name' => 'Test Admin',
            'email' => 'test.admin@abbrevio.com',
            'password' => bcrypt('test1234'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin korisnici uspješno stvoreni!');
    }
}
