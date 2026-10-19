<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Site\CheckoutController;
use App\Http\Controllers\Site\MercadoPagoWebhookController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

Route::get('/', HomeController::class)->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Everything Revit-related lives under /revit (URL and route names).
// The routes themselves are in routes/revit.php, without the prefix.
Route::prefix('revit')->name('revit.')->group(base_path('routes/revit.php'));

// Checkout: needs a logged-in, verified user. Guests are sent to the login and
// come back here afterwards (intended URL).
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/assinar/{plan:slug}', [CheckoutController::class, 'show'])->name('checkout.show');

    Route::post('/assinar/{plan:slug}', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');

    Route::get('/assinatura/retorno', [CheckoutController::class, 'finish'])->name('checkout.finish');
});

// Called by Mercado Pago's servers: no session and no CSRF token. The x-signature header is the protection.
Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->withoutMiddleware([PreventRequestForgery::class])
    ->middleware('throttle:120,1')
    ->name('webhooks.mercadopago');

Route::view('/termos-de-uso', 'site.legal.terms')->name('legal.terms');
Route::view('/politica-de-privacidade', 'site.legal.privacy')->name('legal.privacy');
