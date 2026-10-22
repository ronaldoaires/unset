<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function apiLogin(array $overrides = [])
{
    return test()->postJson('/api/v1/auth/login', array_merge([
        'email'       => 'ana@example.com',
        'password'    => 'password',
        'device_name' => 'revit-plugin@PC-01',
    ], $overrides));
}

beforeEach(function () {
    // The factory password is "password" and the e-mail comes verified.
    $this->user = User::factory()->create(['email' => 'ana@example.com']);
});

it('logs in and returns a token that works', function () {
    $response = apiLogin()
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'email']]);

    $this->withToken($response->json('token'))
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'ana@example.com');
});

it('rejects a wrong password', function () {
    apiLogin(['password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('requires a device name', function () {
    apiLogin(['device_name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('device_name');
});

it('blocks an inactive account', function () {
    $this->user->update(['is_active' => false]);

    apiLogin()->assertForbidden()->assertJsonPath('code', 'account_inactive');
});

it('blocks an unverified e-mail', function () {
    $this->user->forceFill(['email_verified_at' => null])->save();

    apiLogin()->assertForbidden()->assertJsonPath('code', 'email_not_verified');
});

it('answers 401 as JSON even without the Accept header', function () {
    $this->get('/api/v1/auth/me', ['Accept' => 'text/html'])
        ->assertUnauthorized()
        ->assertJsonStructure(['message']);
});

it('revokes the token on logout', function () {
    $token = apiLogin()->json('token');

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($this->user->tokens()->count())->toBe(0);
});

it('replaces the token of the same device', function () {
    apiLogin();
    apiLogin();

    expect($this->user->tokens()->count())->toBe(1);
});

it('keeps at most five devices', function () {
    foreach (range(1, 7) as $i) {
        apiLogin(['device_name' => "pc-{$i}"]);
    }

    expect($this->user->tokens()->count())->toBe(5);
});

it('blocks a deactivated user who still holds a token', function () {
    $token = apiLogin()->json('token');

    $this->user->update(['is_active' => false]);

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertForbidden()
        ->assertJsonPath('code', 'account_inactive');

    expect($this->user->tokens()->count())->toBe(0);
});

it('throttles after five failed attempts', function () {
    foreach (range(1, 5) as $i) {
        apiLogin(['password' => 'wrong'])->assertUnprocessable();
    }

    apiLogin()->assertStatus(429)->assertJsonPath('code', 'too_many_attempts');
});