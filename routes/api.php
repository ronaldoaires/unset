<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FamilyDownloadController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\DownloadHistoryController;
use App\Http\Controllers\Api\V1\PluginReleaseController;
use App\Http\Controllers\Api\V1\DeviceAuthController;

// The "/api" prefix comes from bootstrap/app.php; versioning lives here.
Route::prefix('v1')->name('api.v1.')->group(function () {

    // Each public limit has its own prefix: unnamed throttles share one
    // counter per IP, so polling would eat the other routes' limits.

    // Public: an old or signed-out plugin must still learn it has to update.
    Route::get('plugin/latest', PluginReleaseController::class)
        ->middleware('throttle:60,1,api-plugin')
        ->name('plugin.latest');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1')
        ->name('auth.login');

    // Browser sign-in: the client starts a request, the user approves it on
    // the site, and the client polls for its token.
    Route::post('auth/device', [DeviceAuthController::class, 'start'])
        ->middleware('throttle:10,1,api-device-start')
        ->name('auth.device.start');

    Route::post('auth/device/token', [DeviceAuthController::class, 'token'])
        ->middleware('throttle:30,1,api-device-token')
        ->name('auth.device.token');

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class, 'throttle:120,1'])->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('brands', [BrandController::class, 'index'])->name('brands.index');

        Route::get('families', [FamilyController::class, 'index'])->name('families.index');
        Route::get('families/{family:slug}', [FamilyController::class, 'show'])->name('families.show');

        Route::get('families/{family:slug}/download', FamilyDownloadController::class)
            ->middleware('throttle:20,1,api-download')
            ->name('families.download');

        // The user's own lists. Favorites are open to every verified user.
        Route::get('me/favorites', [FavoriteController::class, 'index'])->name('me.favorites.index');
        Route::get('me/downloads', DownloadHistoryController::class)->name('me.downloads.index');

        Route::post('families/{family:slug}/favorite', [FavoriteController::class, 'store'])->name('families.favorite.store');
        Route::delete('families/{family:slug}/favorite', [FavoriteController::class, 'destroy'])->name('families.favorite.destroy');
    });
});
