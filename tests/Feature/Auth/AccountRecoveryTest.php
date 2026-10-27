<?php

use App\Models\User;
use App\Support\BotTrap;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

// ─── Forgot password ─────────────────────────────────────────────

it('sends the reset link to a person', function () {
    $user = User::factory()->create();

    $this->post('/esqueci-minha-senha', ['email' => $user->email, BotTrap::TIME_FIELD => formToken()])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('gives a bot the same answer but sends no reset link', function () {
    $user = User::factory()->create();

    $this->post('/esqueci-minha-senha', [
        'email'                 => $user->email,
        BotTrap::TIME_FIELD     => formToken(),
        BotTrap::HONEYPOT_FIELD => 'http://spam.example',
    ])->assertSessionHas('status');

    Notification::assertNothingSent();
});

it('asks again when the reset form is sent too fast', function () {
    $user = User::factory()->create();

    $this->post('/esqueci-minha-senha', ['email' => $user->email, BotTrap::TIME_FIELD => BotTrap::token()])
        ->assertSessionHasErrors('email');

    $this->post('/esqueci-minha-senha', ['email' => $user->email])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

// ─── Resend activation e-mail ────────────────────────────────────

it('resends the activation e-mail to a person', function () {
    $user = User::factory()->unverified()->create();

    $this->post('/ativar-conta/reenviar', ['email' => $user->email, BotTrap::TIME_FIELD => formToken()])
        ->assertRedirect(route('register.done'));

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('gives a bot the same answer but resends nothing', function () {
    $user = User::factory()->unverified()->create();

    $this->post('/ativar-conta/reenviar', [
        'email'                 => $user->email,
        BotTrap::TIME_FIELD     => formToken(),
        BotTrap::HONEYPOT_FIELD => 'x',
    ])->assertRedirect(route('register.done'));

    Notification::assertNothingSent();
});

it('asks again when the resend form is sent too fast', function () {
    $user = User::factory()->unverified()->create();

    $this->post('/ativar-conta/reenviar', ['email' => $user->email, BotTrap::TIME_FIELD => BotTrap::token()])
        ->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

it('requires Turnstile on these forms when it is configured', function () {
    config(['services.turnstile.site_key' => 'site', 'services.turnstile.secret_key' => 'secret']);

    $user = User::factory()->unverified()->create();

    $this->post('/esqueci-minha-senha', ['email' => $user->email, BotTrap::TIME_FIELD => formToken()])
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->post('/ativar-conta/reenviar', ['email' => $user->email, BotTrap::TIME_FIELD => formToken()])
        ->assertSessionHasErrors('cf-turnstile-response');

    Notification::assertNothingSent();
});
