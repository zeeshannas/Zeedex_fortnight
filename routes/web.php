<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroceryController;

Route::group(['prefix' => 'crud'], function () {
    Route::match(['get', 'post'], 'categories', [GroceryController::class, 'category']);
    Route::match(['get', 'post'], 'subcategories', [GroceryController::class, 'subcategory']);
    Route::match(['get', 'post'], 'products', [GroceryController::class, 'product']);
});
