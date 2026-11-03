<?php

use App\Http\Controllers\Site\Showcase\PortfolioController;
use App\Http\Controllers\Site\Showcase\ServiceController;
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
