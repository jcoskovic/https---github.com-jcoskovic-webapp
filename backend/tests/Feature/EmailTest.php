<?php

namespace Tests\Feature;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_email_is_sent()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        // Check that email was sent
        Mail::assertSent(PasswordResetMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_password_reset_email_not_sent_for_invalid_email()
    {
        Mail::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(404);

        // Check that no email was sent
        Mail::assertNotSent(PasswordResetMail::class);
    }

    public function test_password_reset_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // First request password reset
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);

        // Check that reset token was created
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $user->refresh();
        $this->assertNotNull($user->password_reset_token);
    }

    public function test_password_can_be_reset_with_valid_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Set a reset token
        $token = 'valid-reset-token';
        $user->password_reset_token = $token;
        $user->password_reset_expires = now()->addHour();
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        // Check that token was cleared
        $user->refresh();
        $this->assertNull($user->password_reset_token);
    }

    public function test_password_reset_fails_with_expired_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        // Set an expired reset token
        $token = 'expired-token';
        $user->password_reset_token = $token;
        $user->password_reset_expires = now()->subHour(); // Expired
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
    }

    public function test_password_reset_fails_with_invalid_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
    }

    public function test_email_validation_for_password_reset()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
