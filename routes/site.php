<?php

use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Everything Revit-related lives under /revit (URL and route names).
// The routes themselves are in routes/revit.php, without the prefix.
Route::prefix('revit')->name('revit.')->group(base_path('routes/revit.php'));