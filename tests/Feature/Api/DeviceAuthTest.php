<?php

use App\Models\DeviceAuthorization;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

function startDeviceLogin(string $device = 'Revit 2025 - PC-01'): array
{
    return test()->postJson('/api/v1/auth/device', ['device_name' => $device])
        ->assertOk()
        ->json();
}

function pollDeviceToken(string $deviceCode)
{
    return test()->postJson('/api/v1/auth/device/token', ['device_code' => $deviceCode]);
}

it('starts a request and stores only the hash of the device code', function () {
    $start = startDeviceLogin();

    expect($start['user_code'])->toMatch('/^[B-Z]{4}-[B-Z]{4}$/')
        ->and($start['expires_in'])->toBe(600)
        ->and($start['interval'])->toBe(5)
        ->and($start['verification_uri'])->toBe(route('device.show'))
        ->and($start['verification_uri_complete'])->toContain('codigo=');

    $row = DeviceAuthorization::sole();

    expect($row->device_code_hash)->toBe(hash('sha256', $start['device_code']))
        ->and($row->user_code)->toBe(str_replace('-', '', $start['user_code']));
});

it('requires a device name', function () {
    $this->postJson('/api/v1/auth/device', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('device_name');
});

it('sends guests to the login', function () {
    $this->get('/conectar?codigo=BCDF-GHJK')->assertRedirect(route('login'));
});

it('signs the plugin in after the user approves on the site', function () {
    $start = startDeviceLogin();

    // Lower case and spaces are accepted, as typed by a person.
    $code = strtolower(str_replace('-', ' ', $start['user_code']));

    $this->actingAs($this->user)
        ->get('/conectar?codigo=' . urlencode($code))
        ->assertOk()
        ->assertSee($start['user_code'])
        ->assertSee('Revit 2025 - PC-01');

    $this->actingAs($this->user)
        ->post('/conectar', ['codigo' => $start['user_code'], 'action' => 'approve'])
        ->assertRedirect(route('device.done'));

    $token = pollDeviceToken($start['device_code'])
        ->assertOk()
        ->assertJsonPath('user.id', $this->user->id)
        ->assertJsonStructure(['token', 'token_type', 'expires_at', 'user', 'access'])
        ->json('token');

    expect($this->user->tokens()->sole()->name)->toBe('Revit 2025 - PC-01');

    $this->app['auth']->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

    // The device code works only once.
    pollDeviceToken($start['device_code'])->assertStatus(410)->assertJsonPath('code', 'expired_token');
});

it('answers pending and asks the client to slow down', function () {
    $start = startDeviceLogin();

    pollDeviceToken($start['device_code'])->assertStatus(400)->assertJsonPath('code', 'authorization_pending');
    pollDeviceToken($start['device_code'])->assertStatus(429)->assertJsonPath('code', 'slow_down');

    $this->travel(5)->seconds();

    pollDeviceToken($start['device_code'])->assertStatus(400)->assertJsonPath('code', 'authorization_pending');
});

it('tells the client when the user denies', function () {
    $start = startDeviceLogin();

    $this->actingAs($this->user)
        ->post('/conectar', ['codigo' => $start['user_code'], 'action' => 'deny'])
        ->assertRedirect(route('device.done'));

    pollDeviceToken($start['device_code'])->assertForbidden()->assertJsonPath('code', 'access_denied');

    expect($this->user->tokens()->count())->toBe(0);
});

it('expires after ten minutes', function () {
    $start = startDeviceLogin();

    $this->travel(11)->minutes();

    $this->actingAs($this->user)
        ->get('/conectar?codigo=' . $start['user_code'])
        ->assertOk()
        ->assertSee('Código inválido ou expirado');

    $this->actingAs($this->user)
        ->post('/conectar', ['codigo' => $start['user_code'], 'action' => 'approve'])
        ->assertRedirect(route('device.show'))
        ->assertSessionHasErrors('codigo');

    pollDeviceToken($start['device_code'])->assertStatus(410)->assertJsonPath('code', 'expired_token');
});

it('answers expired for an unknown device code', function () {
    pollDeviceToken(str_repeat('a', 64))->assertStatus(410)->assertJsonPath('code', 'expired_token');
});

it('keeps only the first answer', function () {
    $start = startDeviceLogin();

    $this->actingAs($this->user)
        ->post('/conectar', ['codigo' => $start['user_code'], 'action' => 'deny']);

    $this->actingAs($this->user)
        ->post('/conectar', ['codigo' => $start['user_code'], 'action' => 'approve'])
        ->assertSessionHasErrors('codigo');

    pollDeviceToken($start['device_code'])->assertForbidden();
});

it('does not hand a token to an account deactivated after the approval', function () {
    $start = startDeviceLogin();

    $this->actingAs($this->user)
        ->post('/conectar', ['codigo' => $start['user_code'], 'action' => 'approve']);

    $this->user->update(['is_active' => false]);

    pollDeviceToken($start['device_code'])->assertForbidden()->assertJsonPath('code', 'account_inactive');

    expect($this->user->tokens()->count())->toBe(0);
});
