<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductTypeController;
use App\Http\Controllers\ProductController;
// use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
// use App\Http\Controllers\CartItemController;
use App\Http\Controllers\ProductOptionController;
// use App\Http\Controllers\KhqrController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\BranchProductController;
use App\Http\Controllers\StoreFrontController;

/*
|--------------------------------------------------------------------------
| 1. Public API Endpoints (No Authentication Guards Required)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/auth/register/request-otp', [AuthController::class, 'requestRegisterOtp']);
Route::post('/auth/register/verify-otp', [AuthController::class, 'verifyRegisterOtp']);

// Catalog Sifting & Public Browsing
Route::get('/product-categories',      [ProductCategoryController::class, 'index']);
Route::get('/product-categories/{productCategory}', [ProductCategoryController::class, 'show']);

Route::get('/product-types',       [ProductTypeController::class, 'index']);
Route::get('/product-types/{productType}',  [ProductTypeController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::get('/branch-products', [BranchProductController::class, 'index']);

Route::get('/storefront/home',       [StoreFrontController::class, 'home']);

// Public store & branch browsing
Route::get('/stores',          [StoreController::class, 'index']);
Route::get('/stores/{store}',     [StoreController::class, 'show']);
Route::get('/branches',        [BranchController::class, 'index']);
Route::get('/branches/{branch}',   [BranchController::class, 'show']);

// System Status Code Configurations (Readable by all active profiles)
Route::get('/statuses',          [StatusController::class, 'index']);
Route::get('/statuses/{status}', [StatusController::class, 'show']);

/*
|--------------------------------------------------------------------------
| 2. Core Authenticated Routes (Protected by JWT Bearer Tokens)
|--------------------------------------------------------------------------
*/

Route::middleware(['api', 'auth:api', 'active'])->group(function () {
    Route::get('/me',        [AuthController::class, 'me']);
    Route::post('/logout',   [AuthController::class, 'logout']);
    Route::post('/refresh',  [AuthController::class, 'refresh']);

    // Addresses Module (Role filtering built directly into the Controller logic)
    Route::get('/addresses',  [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{id}', [AddressController::class, 'show']);
    // Route::get('/addresses/{userId}', [AddressController::class, 'getByUserId']);
    Route::put('/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    // Favorites Module Group
    Route::get('/favorites',            [FavoriteController::class, 'index']);
    Route::post('/favorites',           [FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}',    [FavoriteController::class, 'destroy']);

    // Unified Shopping Cart Engine (Manages lines internally)
    Route::get('/cart',                 [CartController::class, 'viewCart']);
    Route::post('/cart/add',            [CartController::class, 'addItem']);
    Route::delete('/cart/item/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/cart/clear',         [CartController::class, 'clearCart']);

    // Transactional Orders Flow Pipeline
    Route::get('/orders',               [OrderController::class, 'index']);
    Route::post('/orders',              [OrderController::class, 'store']); // Acts as Checkout endpoint
    Route::get('/orders/{order}',       [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']); // Returns stock numbers online
    Route::post('/orders/{order}/confirm-paid', [OrderController::class, 'confirmPaid']);

    // Multi-Payment Gateway Processor Hub
    Route::get('/payments',             [PaymentController::class, 'index']);
    Route::get('/payments/{payment}',   [PaymentController::class, 'show']);
    Route::post('/payments/process',    [PaymentController::class, 'processPayment']); // Stripe, ABA, KHQR, COD, Cashier
    Route::post('/payments/{id}/confirm-sandbox', [PaymentController::class, 'confirmSandboxPayment']);

    Route::get('/refunds', [RefundController::class, 'index']);
    Route::post('/refunds', [RefundController::class, 'store']);
    Route::get('/refunds/{refund}', [RefundController::class, 'show']);

    Route::post('/upload',              [FileUploadController::class, 'upload']);

    Route::patch('/users/{targetUser}/status', [UserController::class, 'updateStatus']);

     /*
    |--------------------------------------------------------------------------
    | 3. Elevated Administrative Domain Controls
    |--------------------------------------------------------------------------
    */

    // Operational Access: Store Admins & Super Admins (Catalog Control Lines)
    Route::middleware(['role:super_admin,store_admin'])->group(function () {
        // Products Catalog Management
        Route::post('/products',            [ProductController::class, 'store']);
        Route::put('/products/{product}',   [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Route::post('/branch-products', [BranchProductController::class, 'store']);
        
        // Isolated Single Sub-variant Parameter Adjustments
        Route::post('/product-options',                [ProductOptionController::class, 'store']);   // Added missing single-option creation
        Route::put('/product-options/{productOption}', [ProductOptionController::class, 'update']);
        Route::delete('/product-options/{productOption}', [ProductOptionController::class, 'destroy']); // Added safe single-option removal
        Route::delete('/product-images/{productImage}', [ProductImageController::class, 'destroy']);

        // Upload Asset Elements Storage Tooling
        

        Route::put('/orders/{order}',       [OrderController::class, 'update']); // Order status processing transitions

        // Subcategory Content Trees Controls
        Route::post('/product-categories',     [ProductCategoryController::class, 'store']);
        Route::put('/product-categories/{productCategory}', [ProductCategoryController::class, 'update']);

        // Product Structural Lines Configurations
        Route::post('/product-types',          [ProductTypeController::class, 'store']);
        Route::put('/product-types/{productType}', [ProductTypeController::class, 'update']);

        // Store Admin Outlets Scope Configurations
        Route::post('/stores',              [StoreController::class, 'store']);
        Route::put('/stores/{store}',       [StoreController::class, 'update']);
        Route::delete('/stores/{store}',    [StoreController::class, 'destroy']);

        Route::post('/branches',            [BranchController::class, 'store']);
        Route::put('/branches/{branch}',    [BranchController::class, 'update']);
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

        Route::post('/refunds/{refund}/review', [RefundController::class, 'review']);

        // Enterprise Internal Profiles Multi-tenant Directory
        // (Store Admins are scoped in-controller to the cashier accounts they created themselves)
        Route::get('/users',                [UserController::class, 'index']);
        Route::get('/users/{targetUser}',   [UserController::class, 'show']);
        Route::post('/users',               [UserController::class, 'store']);
        Route::put('/users/{targetUser}',   [UserController::class, 'update']);
        Route::delete('/users/{targetUser}', [UserController::class, 'destroy']);
    });

    // Administrative Access: Cashiers, Store Admins & Super Admins (Fulfillment Line Controls)
    Route::middleware(['role:super_admin,store_admin,cashier'])->group(function () {
        
        Route::post('/payments/{payment}/settle-cod', [PaymentController::class, 'settleCODPayment']); // Driver cash collections closure

        Route::get('/dashboard', [DashboardController::class, 'getStats']);
    });

    // System-wide Global Admin Controls: Super Admin Only
    Route::middleware(['role:super_admin'])->group(function () {
        
        // Master Infrastructure Analytics
        // Route::get('/dashboard',            [DashboardController::class, 'index']);

        // Lookup State Parameter Management
        Route::post('/statuses',            [StatusController::class, 'store']);
        Route::put('/statuses/{status}',    [StatusController::class, 'update']);
        Route::delete('/statuses/{status}', [StatusController::class, 'destroy']);

        // System Categories Permanent Tree Node Erasures
        Route::delete('/product-categories/{productCategory}', [ProductCategoryController::class, 'destroy']);
        Route::delete('/product-types/{productType}',           [ProductTypeController::class, 'destroy']);
    });
});
