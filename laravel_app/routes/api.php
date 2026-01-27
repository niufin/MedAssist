<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MedicineApiController;
use App\Http\Controllers\Api\IngredientApiController;

Route::middleware(['auth'])->group(function () {
    Route::get('/medicines', [MedicineApiController::class, 'index']);
    Route::get('/medicines/{medicine}', [MedicineApiController::class, 'show']);
    Route::get('/ingredients', [IngredientApiController::class, 'index']);
});
