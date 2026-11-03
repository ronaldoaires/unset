<?php

use App\Http\Controllers\Site\Showcase\ContactController;
use App\Http\Controllers\Site\Showcase\PortfolioController;
use App\Http\Controllers\Site\Showcase\QuoteController;
use App\Http\Controllers\Site\Showcase\ServiceController;
use App\Http\Controllers\Site\Showcase\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Engineering and technology services + portfolio. Loaded by routes/site.php.

$areas = 'engenharia|tecnologia';

Route::get('/servicos', [ServiceController::class, 'index'])->name('services.index');

Route::get('/servicos/{area}', [ServiceController::class, 'area'])
    ->where('area', $areas)
    ->name('services.area');

Route::get('/servicos/{area}/{service:slug}', [ServiceController::class, 'show'])
    ->where('area', $areas)
    ->name('services.show');

Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index');

Route::get('/portfolio/{project:slug}', [PortfolioController::class, 'show'])->name('portfolio.show');

// Quote request (full form) and "Fale conosco" (footer form and /contato).
// Strict throttles: real people send one or two, bots send hundreds.
Route::get('/orcamento', [QuoteController::class, 'create'])->name('quotes.create');

Route::post('/orcamento', [QuoteController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('quotes.store');

Route::get('/contato', [ContactController::class, 'show'])->name('contact.show');

Route::post('/contato', [ContactController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('contact.store');

Route::get('/mensagem-enviada', [QuoteController::class, 'thanks'])->name('leads.thanks');

// POST only (with CSRF): see WhatsAppController for why the number is never in the HTML.
Route::post('/whatsapp', WhatsAppController::class)
    ->middleware('throttle:20,1')
    ->name('whatsapp');
