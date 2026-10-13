<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\Site\FamilyDownloadController;


// Serve files from the storage disk (public) through a controller.
Route::get('/media/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

Route::get('/familias/{family:slug}/baixar', FamilyDownloadController::class)
    ->middleware(['auth', 'verified'])
    ->name('families.download');

// The default Laravel welcome page is kept for now, but it will be removed later.
Route::get('/', function () {
    return view('welcome');
});
