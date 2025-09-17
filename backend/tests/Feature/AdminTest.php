<?php

namespace Tests\Feature;

use App\Models\Abbreviation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser()
    {
        return User::factory()->create(['role' => 'admin']);
    }

    protected function createRegularUser()
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_admin_can_access_statistics()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->getJson('/api/admin/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_abbreviations',
                    'total_users',
                    'pending_abbreviations',
                ],
            ]);
    }

    public function test_regular_user_cannot_access_admin_endpoints()
    {
        /** @var User $user */
        $user = $this->createRegularUser();
        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->getJson('/api/admin/statistics');

        $response->assertStatus(403); // Forbidden
    }

    public function test_admin_can_get_all_users()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        // Create some test users
        User::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_admin_can_get_pending_abbreviations()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        // Create pending abbreviations
        Abbreviation::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->getJson('/api/admin/abbreviations/pending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
            ]);
    }

    public function test_admin_can_approve_abbreviation()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        $abbreviation = Abbreviation::factory()->create(['status' => 'pending']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->postJson("/api/admin/abbreviations/{$abbreviation->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('abbreviations', [
            'id' => $abbreviation->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_reject_abbreviation()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        $abbreviation = Abbreviation::factory()->create(['status' => 'pending']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->postJson("/api/admin/abbreviations/{$abbreviation->id}/reject");

        $response->assertStatus(200);

        $this->assertDatabaseHas('abbreviations', [
            'id' => $abbreviation->id,
            'status' => 'rejected',
        ]);
    }

    public function test_admin_can_promote_user()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        $user = $this->createRegularUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->postJson("/api/admin/users/{$user->id}/promote");

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'moderator',
        ]);
    }

    public function test_admin_can_delete_user()
    {
        /** @var User $admin */
        $admin = $this->createAdminUser();
        $token = auth('api')->login($admin);

        $user = $this->createRegularUser();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])
            ->deleteJson("/api/admin/users/{$user->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
