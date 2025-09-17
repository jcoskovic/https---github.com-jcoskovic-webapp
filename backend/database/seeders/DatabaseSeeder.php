<?php

namespace Database\Seeders;

use App\Models\Abbreviation;
use App\Models\Comment;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo users for faculty evaluation
        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@abbrevio.test',
            'password' => bcrypt('admin1234'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $moderator = User::create([
            'name' => 'Moderator',
            'email' => 'moderator@abbrevio.test',
            'password' => bcrypt('moderator1234'),
            'role' => 'moderator',
            'email_verified_at' => now(),
        ]);

        $user1 = User::create([
            'name' => 'Test User',
            'email' => 'user@abbrevio.test',
            'password' => bcrypt('user1234'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'Marko Marković',
            'email' => 'marko@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $user3 = User::create([
            'name' => 'Ana Anić',
            'email' => 'ana@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        // Dodaj novog admin korisnika
        $newAdmin = User::create([
            'name' => 'Moj Admin',
            'email' => 'moj.admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // Create comprehensive sample abbreviations for ML demonstration
        $abbreviations = [
            // Tehnologija kategorija
            [
                'abbreviation' => 'API',
                'meaning' => 'Application Programming Interface',
                'description' => 'Skup protokola i alata za građenje softverskih aplikacija',
                'category' => 'Tehnologija',
                'user_id' => $admin->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'AI',
                'meaning' => 'Artificial Intelligence',
                'description' => 'Simulacija ljudske inteligencije u strojevima',
                'category' => 'Tehnologija',
                'user_id' => $user2->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'ML',
                'meaning' => 'Machine Learning',
                'description' => 'Podskup umjetne inteligencije koji omogućava strojevima da uče',
                'category' => 'Tehnologija',
                'user_id' => $admin->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'IoT',
                'meaning' => 'Internet of Things',
                'description' => 'Mreža fizičkih objekata povezanih internetom',
                'category' => 'Tehnologija',
                'user_id' => $user1->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'VR',
                'meaning' => 'Virtual Reality',
                'description' => 'Simulirana okolina koja može biti slična stvarnom svijetu',
                'category' => 'Tehnologija',
                'user_id' => $moderator->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'AR',
                'meaning' => 'Augmented Reality',
                'description' => 'Interaktivno iskustvo u stvarnom okolišu',
                'category' => 'Tehnologija',
                'user_id' => $user2->id,
                'status' => 'approved',
            ],

            // Poslovanje kategorija
            [
                'abbreviation' => 'CRM',
                'meaning' => 'Customer Relationship Management',
                'description' => 'Pristup upravljanju interakcijama tvrtke s klijentima',
                'category' => 'Poslovanje',
                'user_id' => $user1->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'HR',
                'meaning' => 'Human Resources',
                'description' => 'Odjel zadužen za upravljanje ljudskim potencijalima',
                'category' => 'Poslovanje',
                'user_id' => $user2->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'SLA',
                'meaning' => 'Service Level Agreement',
                'description' => 'Ugovor koji definira očekivanu razinu usluge',
                'category' => 'Poslovanje',
                'user_id' => $user1->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'ROI',
                'meaning' => 'Return on Investment',
                'description' => 'Mjera efikasnosti investicije',
                'category' => 'Poslovanje',
                'user_id' => $moderator->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'KPI',
                'meaning' => 'Key Performance Indicator',
                'description' => 'Pokazatelj uspješnosti poslovanja',
                'category' => 'Poslovanje',
                'user_id' => $admin->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'B2B',
                'meaning' => 'Business to Business',
                'description' => 'Poslovanje između dviju tvrtki',
                'category' => 'Poslovanje',
                'user_id' => $user1->id,
                'status' => 'approved',
            ],

            // Razvoj kategorija
            [
                'abbreviation' => 'MVP',
                'meaning' => 'Minimum Viable Product',
                'description' => 'Proizvod s minimalnim funkcionalnostima za testiranje tržišta',
                'category' => 'Razvoj',
                'user_id' => $admin->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'CI/CD',
                'meaning' => 'Continuous Integration/Continuous Deployment',
                'description' => 'Praksa kontinuirane integracije i implementacije koda',
                'category' => 'Razvoj',
                'user_id' => $moderator->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'REST',
                'meaning' => 'Representational State Transfer',
                'description' => 'Arhitektonski stil za dizajniranje web servisa',
                'category' => 'Razvoj',
                'user_id' => $user2->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'CRUD',
                'meaning' => 'Create, Read, Update, Delete',
                'description' => 'Osnovne operacije s podacima u bazi',
                'category' => 'Razvoj',
                'user_id' => $admin->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'MVC',
                'meaning' => 'Model-View-Controller',
                'description' => 'Arhitektonski obrazac za organizaciju koda',
                'category' => 'Razvoj',
                'user_id' => $user1->id,
                'status' => 'approved',
            ],

            // Medicinske skraćenice
            [
                'abbreviation' => 'CT',
                'meaning' => 'Computed Tomography',
                'description' => 'Medicinska tehnika snimanja pomoću rendgenskih zraka',
                'category' => 'Medicina',
                'user_id' => $user2->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'MRI',
                'meaning' => 'Magnetic Resonance Imaging',
                'description' => 'Medicinska tehnika snimanja pomoću magnetnog polja',
                'category' => 'Medicina',
                'user_id' => $moderator->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'ICU',
                'meaning' => 'Intensive Care Unit',
                'description' => 'Odjel intenzivnog liječenja u bolnici',
                'category' => 'Medicina',
                'user_id' => $user1->id,
                'status' => 'approved',
            ],

            // Obrazovanje
            [
                'abbreviation' => 'PhD',
                'meaning' => 'Doctor of Philosophy',
                'description' => 'Najviši akademski stupanj u većini zemalja',
                'category' => 'Obrazovanje',
                'user_id' => $admin->id,
                'status' => 'approved',
            ],
            [
                'abbreviation' => 'MBA',
                'meaning' => 'Master of Business Administration',
                'description' => 'Magisterij poslovne administracije',
                'category' => 'Obrazovanje',
                'user_id' => $moderator->id,
                'status' => 'approved',
            ],
        ];

        $createdAbbreviations = [];
        foreach ($abbreviations as $abbr) {
            $abbreviation = Abbreviation::create($abbr);
            $createdAbbreviations[] = $abbreviation;

            // Add diverse voting patterns for ML training data
            $users = [$admin, $user1, $user2, $moderator];
            $voteCount = rand(2, 8); // Random number of votes per abbreviation

            for ($i = 0; $i < $voteCount; $i++) {
                $randomUser = $users[array_rand($users)];
                $voteType = (rand(1, 10) <= 8) ? 'up' : 'down'; // 80% upvotes, 20% downvotes

                // Check if user already voted for this abbreviation
                $existingVote = Vote::where('abbreviation_id', $abbreviation->id)
                    ->where('user_id', $randomUser->id)
                    ->first();

                if (!$existingVote) {
                    Vote::create([
                        'abbreviation_id' => $abbreviation->id,
                        'user_id' => $randomUser->id,
                        'type' => $voteType,
                    ]);
                }
            }
        }

        // Add diverse comments for ML training data
        $comments = [
            ['abbreviation' => 'API', 'content' => 'Vrlo korisna skraćenica u IT svijetu! Svaki developer mora znati.'],
            ['abbreviation' => 'API', 'content' => 'Temelj moderne web arhitekture.'],
            ['abbreviation' => 'AI', 'content' => 'Budućnost tehnologije je već ovdje!'],
            ['abbreviation' => 'AI', 'content' => 'Fascinantno kako brzo se razvija ova oblast.'],
            ['abbreviation' => 'ML', 'content' => 'Koristim machine learning u svom radu svakodnevno.'],
            ['abbreviation' => 'CRM', 'content' => 'Nezamislivo je voditi business bez dobrog CRM sistema.'],
            ['abbreviation' => 'MVP', 'content' => 'Odličan pristup za startup projekte.'],
            ['abbreviation' => 'MVP', 'content' => 'Štedi vrijeme i novac u razvoju proizvoda.'],
            ['abbreviation' => 'IoT', 'content' => 'Internet stvari mijenja način kako živimo.'],
            ['abbreviation' => 'VR', 'content' => 'Gaming industrija se potpuno transformirala.'],
            ['abbreviation' => 'ROI', 'content' => 'Ključna metrika za svaku investiciju.'],
            ['abbreviation' => 'KPI', 'content' => 'Bez KPI-jeva nema mjerenja uspjeha.'],
            ['abbreviation' => 'CI/CD', 'content' => 'DevOps best practice koji svaki tim treba implementirati.'],
            ['abbreviation' => 'REST', 'content' => 'Standard za web API dizajn.'],
            ['abbreviation' => 'MVC', 'content' => 'Klasičan pattern koji još uvijek radi odlično.'],
            ['abbreviation' => 'PhD', 'content' => 'Dugotrajan ali vrlo nagrađujući put.'],
            ['abbreviation' => 'MBA', 'content' => 'Otvara mnoge poslovne prilika.'],
            ['abbreviation' => 'CT', 'content' => 'Revolucionarna tehnologija u medicini.'],
            ['abbreviation' => 'MRI', 'content' => 'Detaljnije od CT skeniranja.'],
            ['abbreviation' => 'ICU', 'content' => 'Heroji koji rade u ICU-ima spašavaju živote.'],
        ];

        $users = [$admin, $user1, $user2, $moderator];

        foreach ($comments as $commentData) {
            $abbreviation = Abbreviation::where('abbreviation', $commentData['abbreviation'])->first();
            if ($abbreviation) {
                Comment::create([
                    'abbreviation_id' => $abbreviation->id,
                    'user_id' => $users[array_rand($users)]->id,
                    'content' => $commentData['content'],
                ]);
            }
        }

        // Pozovi AdminSeeder
        $this->call(AdminSeeder::class);

        $this->command->info('Sample data created successfully!');
    }
}
