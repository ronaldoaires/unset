<?php

use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\EmailController;
use App\Http\Controllers\Account\AvatarController;
use App\Http\Controllers\Account\FavoriteController;
use App\Http\Controllers\Account\DownloadController;
use App\Http\Controllers\Account\SubscriptionController;

// The "/conta" prefix and the "account." name prefix come from routes/web.php.

Route::get('/', DashboardController::class)->name('index');

Route::get('/perfil', [ProfileController::class, 'edit'])->name('profile');
Route::put('/perfil', [ProfileController::class, 'update'])->name('profile.update');

Route::get('/senha', [PasswordController::class, 'edit'])->name('password');
Route::put('/senha', [PasswordController::class, 'update'])
    ->middleware('throttle:6,1')
    ->name('password.update');

Route::get('/email', [EmailController::class, 'edit'])->name('email');
Route::put('/email', [EmailController::class, 'update'])
    ->middleware('throttle:5,10')
    ->name('email.update');
Route::delete('/email', [EmailController::class, 'cancel'])->name('email.cancel');

Route::get('/avatar', [AvatarController::class, 'edit'])->name('avatar');
Route::post('/avatar', [AvatarController::class, 'update'])
    ->middleware('throttle:10,1')
    ->name('avatar.update');
Route::delete('/avatar', [AvatarController::class, 'destroy'])->name('avatar.destroy');

Route::get('/favoritos', [FavoriteController::class, 'index'])->name('favorites');

Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads');

Route::get('/assinatura', [SubscriptionController::class, 'index'])->name('subscription');