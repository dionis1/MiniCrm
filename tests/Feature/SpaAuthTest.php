<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_user_and_authenticated_session(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'CRM Tester',
            'email' => 'crm@example.test',
            'password' => 'secure-long-password-123',
            'password_confirmation' => 'secure-long-password-123',
        ]);
        $response->assertCreated()->assertJsonPath('data.name', 'CRM Tester');
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secure-long-password-123', $user->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_credentials_are_rejected_without_a_session(): void
    {
        $user = User::factory()->create();
        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'not-the-password',
        ])->assertUnprocessable();

        $this->assertGuest();
    }

    public function test_login_and_logout_invalidate_session(): void
    {
        $user = User::factory()->create(['password' => 'secure-long-password-123']);
        $this->postJson('/login', ['email' => $user->email, 'password' => 'secure-long-password-123'])
            ->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->postJson('/logout')->assertOk();
        $this->assertGuest();
    }

    public function test_registration_validates_password_confirmation_and_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);
        $this->postJson('/register', [
            'name' => 'Other',
            'email' => 'taken@example.test',
            'password' => 'secure-long-password-123',
            'password_confirmation' => 'another-password',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }
}
