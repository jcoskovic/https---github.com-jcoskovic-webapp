<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_export_pdf()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Create some test data to export
        Abbreviation::factory()->count(3)->create(['status' => 'approved']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])
            ->getJson('/api/export/pdf');

        $response->assertStatus(200);

        // Check that response is PDF
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_unauthenticated_user_cannot_export()
    {
        $response = $this->getJson('/api/export/pdf');
        $response->assertStatus(401);
    }

    public function test_pdf_export_contains_data()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        // Create specific test abbreviation
        $abbreviation = Abbreviation::factory()->create([
            'abbreviation' => 'TEST',
            'meaning' => 'Test Export',
            'status' => 'approved',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])
            ->getJson('/api/export/pdf');

        $response->assertStatus(200);

        // For PDF, we can at least check that it's not empty
        $this->assertGreaterThan(1000, strlen($response->getContent()));
    }
}
