<?php

use App\Http\Controllers\Site\Revit\FamilyController;
use App\Http\Controllers\Site\Revit\FamilyDownloadController;
use Illuminate\Support\Facades\Route;

// The "/revit" prefix and the "revit." name prefix come from routes/site.php.

Route::get('/familias', [FamilyController::class, 'index'])->name('families.index');

Route::get('/familias/{family:slug}', [FamilyController::class, 'show'])->name('families.show');

Route::get('/familias/{family:slug}/baixar', FamilyDownloadController::class)
    ->middleware(['auth', 'verified', 'throttle:20,1'])
    ->name('families.download');
