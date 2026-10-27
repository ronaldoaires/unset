<?php

use App\Models\User;
use App\Support\BotTrap;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

/*
 * The e-mail rule checks the domain's DNS, so these tests use real domains
 * (gmail.com) and need an internet connection.
 */

beforeEach(function () {
    Notification::fake();
});

/**
 * A valid sign-up as a person sends it: form rendered 10 seconds ago.
 */
function signUpData(array $overrides = []): array
{
    test()->travel(-10)->seconds();
    $token = BotTrap::token();
    test()->travelBack();

    return array_merge([
        'name'                  => 'Maria Souza',
        'email'                 => 'maria.souza@gmail.com',
        'password'              => 'Senha12345',
        'password_confirmation' => 'Senha12345',
        BotTrap::TIME_FIELD     => $token,
    ], $overrides);
}

it('registers a real person', function () {
    $this->post('/cadastro', signUpData(['name' => "  Maria   D'Ávila-Souza Jr. ", 'email' => ' Maria.Souza@Gmail.com ']))
        ->assertRedirect(route('register.done'));

    $user = User::sole();

    expect($user->name)->toBe("Maria D'Ávila-Souza Jr.")
        ->and($user->email)->toBe('maria.souza@gmail.com');
});

it('rejects spam names', function (string $name) {
    $this->post('/cadastro', signUpData(['name' => $name]))->assertSessionHasErrors('name');

    expect(User::count())->toBe(0);
})->with([
    'link'      => '* * * <a href="http://advodocs.ru/go-na.php?n1pq8k">$3,222 deposit available</a> * * *',
    'hash'      => 'hs=fdf62b27504a18959de8b5482155e076',
    'asterisks' => 'ххх*',
    'digits'    => 'Maria 123',
    'one char'  => 'A',
    'repeated'  => 'Aaaaaa',
]);

it('rejects strange and temporary e-mails', function (string $email) {
    $this->post('/cadastro', signUpData(['email' => $email]))->assertSessionHasErrors('email');

    expect(User::count())->toBe(0);
})->with([
    'tilde'      => 'bgg~671lbkbz0p7mt7ywd@gmail.com',
    'disposable' => 'teste.cadastro@mailinator.com',
    'spam list'  => 'joao.silva@fextemp.com',
    'own list'   => 'teofilo-ferruc91@m.spheremail.net',
    'no domain'  => 'maria@dominio-que-nao-existe-unset-test.com.br',
]);

it('pretends success to a bot that fills the honeypot', function () {
    $this->post('/cadastro', signUpData([BotTrap::HONEYPOT_FIELD => 'http://spam.example']))
        ->assertRedirect(route('register.done'));

    expect(User::count())->toBe(0);
    Notification::assertNothingSent();
});

it('asks again when the form is sent too fast or without its token', function () {
    $this->post('/cadastro', signUpData([BotTrap::TIME_FIELD => BotTrap::token()]))
        ->assertSessionHasErrors('email');

    $this->post('/cadastro', signUpData([BotTrap::TIME_FIELD => 'forged']))
        ->assertSessionHasErrors('email');

    expect(User::count())->toBe(0);
});

it('removes abandoned unverified accounts on a new sign-up', function () {
    $old      = User::factory()->unverified()->create(['created_at' => now()->subDays(8)]);
    $recent   = User::factory()->unverified()->create(['created_at' => now()->subDays(2)]);
    $verified = User::factory()->create(['created_at' => now()->subDays(30)]);

    $this->post('/cadastro', signUpData())->assertRedirect(route('register.done'));

    expect(User::find($old->id))->toBeNull()
        ->and(User::find($recent->id))->not->toBeNull()
        ->and(User::find($verified->id))->not->toBeNull();
});

it('requires Turnstile when it is configured', function () {
    config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);

    // Missing token: no call. Then Cloudflare refuses once and accepts once.
    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
        ->push(['success' => false])
        ->push(['success' => true])]);

    $this->post('/cadastro', signUpData())
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->post('/cadastro', signUpData(['cf-turnstile-response' => 'bad']))
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->post('/cadastro', signUpData(['cf-turnstile-response' => 'good']))
        ->assertRedirect(route('register.done'));

    $this->post('/cadastro', signUpData(['cf-turnstile-response' => 'good']))
        ->assertRedirect(route('register.done'));
});

it('applies the same name rule to the profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('account.profile.update'), ['name' => '<a href="http://x.ru">ganhe</a>'])
        ->assertSessionHasErrors('name');
});
