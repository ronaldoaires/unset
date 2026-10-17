<?php

use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\ProfileController;
use Illuminate\Support\Facades\Route;

// The "/conta" prefix and the "account." name prefix come from routes/web.php.

Route::get('/', DashboardController::class)->name('index');

Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile');
Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');