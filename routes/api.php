<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Product CRUD endpoints
Route::apiResource('products', ProductController::class);

// Search endpoint
Route::get('search/products', [SearchController::class, 'searchProducts']);

// Image upload endpoint
Route::post('products/{id}/image', [ProductController::class, 'uploadImage']);
