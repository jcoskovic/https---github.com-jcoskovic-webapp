<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Abbreviation;

class ProductionSeeder extends Seeder
{
    /**
     * Run the production database seeds - minimal demo data
     */
    public function run(): void
    {
        // Create demo users
        $admin = User::firstOrCreate(
            ['email' => 'admin@abbrevio.demo'],
            [
                'name' => 'Demo Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@abbrevio.demo'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        $moderator = User::firstOrCreate(
            ['email' => 'moderator@abbrevio.demo'],
            [
                'name' => 'Demo Moderator',
                'password' => Hash::make('moderator123'),
                'role' => 'moderator',
                'email_verified_at' => now(),
            ]
        );

        // Create some demo abbreviations
        $abbreviations = [
            [
                'title' => 'API',
                'meaning' => 'Application Programming Interface',
                'category' => 'Technology',
                'description' => 'A set of protocols and tools for building software applications.',
                'status' => 'approved',
                'user_id' => $admin->id,
            ],
            [
                'title' => 'HTTP',
                'meaning' => 'Hypertext Transfer Protocol',
                'category' => 'Technology',
                'description' => 'Protocol used for transmitting web pages over the internet.',
                'status' => 'approved',
                'user_id' => $user->id,
            ],
            [
                'title' => 'SQL',
                'meaning' => 'Structured Query Language',
                'category' => 'Technology',
                'description' => 'Programming language designed for managing relational databases.',
                'status' => 'approved',
                'user_id' => $moderator->id,
            ],
            [
                'title' => 'AI',
                'meaning' => 'Artificial Intelligence',
                'category' => 'Technology',
                'description' => 'Computer systems able to perform tasks that typically require human intelligence.',
                'status' => 'approved',
                'user_id' => $admin->id,
            ],
            [
                'title' => 'ML',
                'meaning' => 'Machine Learning',
                'category' => 'Technology',
                'description' => 'Type of AI that enables computers to learn without explicit programming.',
                'status' => 'approved',
                'user_id' => $user->id,
            ],
        ];

        foreach ($abbreviations as $abbr) {
            Abbreviation::firstOrCreate(
                ['title' => $abbr['title']],
                $abbr
            );
        }

        $this->command->info('✅ Production demo data seeded successfully!');
        $this->command->info('📧 Demo users created:');
        $this->command->info('   Admin: admin@abbrevio.demo / admin123');
        $this->command->info('   User: user@abbrevio.demo / user123');
        $this->command->info('   Moderator: moderator@abbrevio.demo / moderator123');
    }
}