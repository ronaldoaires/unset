<?php

use App\Http\Controllers\Site\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Everything Revit-related lives under /revit (URL and route names).
// The routes themselves are in routes/revit.php, without the prefix.
Route::prefix('revit')->name('revit.')->group(base_path('routes/revit.php'));