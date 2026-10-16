<?php

use App\Http\Controllers\Site\Revit\BrandController;
use App\Http\Controllers\Site\Revit\CategoryController;
use App\Http\Controllers\Site\Revit\FamilyController;
use App\Http\Controllers\Site\Revit\FamilyDownloadController;
use App\Http\Controllers\Site\Revit\SubcategoryController;
use Illuminate\Support\Facades\Route;

// The "/revit" prefix and the "revit." name prefix come from routes/site.php.

Route::get('/familias', [FamilyController::class, 'index'])->name('families.index');

Route::get('/familias/{family:slug}', [FamilyController::class, 'show'])->name('families.show');

Route::get('/familias/{family:slug}/baixar', FamilyDownloadController::class)
    ->middleware(['auth', 'verified', 'throttle:20,1'])
    ->name('families.download');

Route::get('/categorias/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');

// A subcategory slug is unique only inside its category, so the binding is scoped to the parent.
Route::get('/categorias/{category:slug}/{subcategory:slug}', [SubcategoryController::class, 'show'])
    ->scopeBindings()
    ->name('subcategories.show');

Route::get('/marcas/{brand:slug}', [BrandController::class, 'show'])->name('brands.show');