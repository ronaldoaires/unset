<?php

use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

// Serve files from the public disk through a controller.
Route::get('/media/{path}', [StorageController::class, 'serve'])
    ->where('path', '.*')
    ->name('storage.serve');

// One route file per context. Account routes will be added here later.
require __DIR__ . '/site.php';
require __DIR__ . '/auth.php';

// Logged-in user area (routes/account.php). Never indexed.
Route::prefix('conta')
    ->name('account.')
    ->middleware(['auth', 'verified'])
    ->group(base_path('routes/account.php'));