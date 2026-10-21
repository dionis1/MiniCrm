<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('resets a password using a single-use token without revealing account existence', function () {
    Notification::fake();
    $user = User::factory()->create();
    $known = $this->postJson('/forgot-password', ['email' => $user->email])->assertOk()->json();
    $unknown = $this->postJson('/forgot-password', ['email' => 'missing@example.test'])->assertOk()->json();
    expect($known)->toBe($unknown);
    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
        $token = $notification->token;

        return true;
    });
    $input = ['email' => $user->email, 'token' => $token, 'password' => 'UpdatedSecure!2026', 'password_confirmation' => 'UpdatedSecure!2026'];
    $this->postJson('/reset-password', $input)->assertOk();
    expect(Hash::check($input['password'], $user->fresh()->password))->toBeTrue();
    $this->postJson('/reset-password', $input)->assertUnprocessable();
});

it('rejects expired reset tokens and short passwords', function () {
    $user = User::factory()->create();
    $token = app('auth.password.broker')->createToken($user);
    $input = ['email' => $user->email, 'token' => $token, 'password' => 'short', 'password_confirmation' => 'short'];
    $this->postJson('/reset-password', $input)->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->travel(61)->minutes();
    $input['password'] = $input['password_confirmation'] = 'UpdatedSecure!2026';
    $this->postJson('/reset-password', $input)->assertUnprocessable();
});
