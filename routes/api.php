<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FamilyDownloadController;

// The "/api" prefix comes from bootstrap/app.php; versioning lives here.
Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:20,1')
        ->name('auth.login');

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
    });
});
