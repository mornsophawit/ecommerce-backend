<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\UserController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/product-types',       [ProductTypeController::class, 'index']);
Route::get('/product-types/{id}',  [ProductTypeController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/by-type/{product_type_id}', [ProductController::class, 'getByType']);
Route::post('/products/query', [ProductController::class, 'query']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('/me',        [AuthController::class, 'me']);
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);

    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{id}', [AddressController::class, 'show']);
    Route::get('/addresses/{userId}', [AddressController::class, 'getByUserId']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    Route::get('/cart', [OrderDetailController::class, 'index']);
    Route::post('/cart', [OrderDetailController::class, 'store']);
    Route::delete('/cart/{id}', [OrderDetailController::class, 'destroy']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/checkout', [OrderController::class, 'checkout']);

    Route::middleware(['role:admin'])->group(function () {
        Route::post('/product-types',        [ProductTypeController::class, 'store']);
        Route::put('/product-types/{id}',    [ProductTypeController::class, 'update']);
        Route::delete('/product-types/{id}', [ProductTypeController::class, 'destroy']);
        Route::get('/addresses', [AddressController::class, 'index']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
    
    Route::middleware(['role:admin,vendor'])->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/upload', [FileUploadController::class, 'upload']);
    });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
