<?php

use App\Http\Controllers\Site\Revit\BrandController;
use App\Http\Controllers\Site\Revit\CategoryController;
use App\Http\Controllers\Site\Revit\FamilyController;
use App\Http\Controllers\Site\Revit\FamilyDownloadController;
use App\Http\Controllers\Site\Revit\SubcategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Site\Revit\FamilyFavoriteController;
use App\Http\Controllers\Site\Revit\FamilyPurchaseController;

// The "/revit" prefix and the "revit." name prefix come from routes/site.php.

Route::get('/familias', [FamilyController::class, 'index'])->name('families.index');

Route::get('/familias/{family:slug}', [FamilyController::class, 'show'])->name('families.show');

Route::get('/familias/{family:slug}/baixar', FamilyDownloadController::class)
    ->middleware(['auth', 'verified', 'throttle:20,1'])
    ->name('families.download');

// One-off purchase of a paid family: summary page, start of the payment and
// the return from Mercado Pago.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/familias/{family:slug}/comprar', [FamilyPurchaseController::class, 'show'])
        ->name('families.purchase.show');

    Route::post('/familias/{family:slug}/comprar', [FamilyPurchaseController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('families.purchase.store');

    Route::get('/compras/{purchase}/retorno', [FamilyPurchaseController::class, 'finish'])
        ->name('purchases.finish');
});

// Favorites: any verified user may favorite a published family. The same URL
// adds (POST) and removes (DELETE), and both are idempotent.
Route::middleware(['auth', 'verified', 'throttle:60,1'])->group(function () {
    Route::post('/familias/{family:slug}/favorito', [FamilyFavoriteController::class, 'store'])
        ->name('families.favorite.store');

    Route::delete('/familias/{family:slug}/favorito', [FamilyFavoriteController::class, 'destroy'])
        ->name('families.favorite.destroy');
});

// Target of the heart for guests: sends them to the login and brings them
// back to the family page afterwards.
Route::get('/familias/{family:slug}/favoritar', [FamilyFavoriteController::class, 'prompt'])
    ->name('families.favorite.prompt');

Route::get('/categorias/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

// A subcategory slug is unique only inside its category, so the binding is scoped to the parent.
Route::get('/categorias/{category:slug}/{subcategory:slug}', [SubcategoryController::class, 'show'])
    ->scopeBindings()
    ->name('subcategories.show');

Route::get('/marcas/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');
