<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GroceryController;
use App\Http\Controllers\DataGridController;

Route::group(['prefix' => 'crud'], function () {
    Route::match(['get', 'post'], 'categories', [GroceryController::class, 'category']);
    Route::match(['get', 'post'], 'subcategories', [GroceryController::class, 'subcategory']);
    Route::match(['get', 'post'], 'products', [GroceryController::class, 'product']);
});

Route::view('datagrid/products', 'datagrid.products');

// API routes for DataGrid
Route::get('api/products', [DataGridController::class, 'index']);
Route::post('api/products', [DataGridController::class, 'store']);
Route::put('api/products/{id}', [DataGridController::class, 'update']);
Route::delete('api/products/{id}', [DataGridController::class, 'destroy']);
