<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Auth\EmailChangeController;

Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/cadastro/concluido', [RegisterController::class, 'done'])->name('register.done');

    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store'])->middleware('throttle:20,1');

    // To add another provider later (e.g. Facebook), include it in whereIn().
    Route::get('/entrar/{provider}', [SocialLoginController::class, 'redirect'])
        ->whereIn('provider', ['google'])
        ->middleware('throttle:20,1')
        ->name('social.redirect');

    Route::get('/entrar/{provider}/retorno', [SocialLoginController::class, 'callback'])
        ->whereIn('provider', ['google'])
        ->middleware('throttle:20,1')
        ->name('social.callback');

    Route::get('/esqueci-minha-senha', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/esqueci-minha-senha', [PasswordResetController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'update'])->name('password.store');
});

// The verification link must work without a session: people usually open it
// from a mail app or another browser where they are not logged in.
// The "signed" middleware already rejects tampered or expired links.
Route::get('/verificar-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->whereNumber('id')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Links of the e-mail change flow also have to work without a session.
Route::get('/alterar-email/confirmar/{id}/{hash}', [EmailChangeController::class, 'confirm'])
    ->whereNumber('id')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('email-change.confirm');

Route::get('/alterar-email/cancelar/{id}/{hash}', [EmailChangeController::class, 'cancel'])
    ->whereNumber('id')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('email-change.cancel');

// Public on purpose: someone blocked at login cannot be authenticated yet.
// The controller always answers with the same message and limits sends per address.
Route::post('/ativar-conta/reenviar', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('verification.resend');

Route::middleware('auth')->group(function () {
    Route::get('/verificar-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verificar-email/reenviar', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('/sair', [LoginController::class, 'destroy'])->name('logout');
});
