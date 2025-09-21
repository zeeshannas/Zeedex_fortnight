<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;


Route::apiResource('categories', Api\CategoryController::class);
Route::apiResource('subcategories', Api\SubcategoryController::class);
Route::apiResource('products', Api\ProductController::class);
