<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\V1\AuthApiController;
use App\Http\Controllers\Api\V1\ProductApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| CSRF excluded globally for API routes.
*/

Route::post('/midtrans/webhook', [MidtransWebhookController::class, '__invoke'])
    ->name('midtrans.webhook');

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // --- Public Routes ---
    Route::get('/', [\App\Http\Controllers\Api\V1\HomeApiController::class, 'index']);
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login']);
    Route::get('/user', [AuthApiController::class, 'user']);
    
    // Product Endpoints (Public)
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/products/{id}', [ProductApiController::class, 'show']);

    // Public Profile Endpoint
    Route::get('/users/{id}', [\App\Http\Controllers\Api\V1\PublicProfileApiController::class, 'show']);

    // --- Protected Routes ---
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Endpoints
        Route::post('/logout', [AuthApiController::class, 'logout']);
        
        // Profile Endpoints
        Route::get('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'show']);
        Route::patch('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'update']);
        Route::delete('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'destroy']);

        // Product Management (Authenticated)
        Route::post('/products', [ProductApiController::class, 'store']);
        Route::patch('/products/{id}', [ProductApiController::class, 'update']);
        Route::delete('/products/{id}', [ProductApiController::class, 'destroy']);

        // Wishlist Endpoints
        Route::get('/wishlists', [\App\Http\Controllers\Api\V1\WishlistApiController::class, 'index']);
        Route::post('/wishlists/{productId}/toggle', [\App\Http\Controllers\Api\V1\WishlistApiController::class, 'toggle']);

        // Cart Endpoints
        Route::get('/carts', [\App\Http\Controllers\Api\V1\CartApiController::class, 'index']);
        Route::post('/carts', [\App\Http\Controllers\Api\V1\CartApiController::class, 'store']);
        Route::delete('/carts/{id}', [\App\Http\Controllers\Api\V1\CartApiController::class, 'destroy']);

        // Offer Endpoints
        Route::get('/offers', [\App\Http\Controllers\Api\V1\OfferApiController::class, 'index']);
        Route::post('/offers', [\App\Http\Controllers\Api\V1\OfferApiController::class, 'store']);
        Route::get('/seller/offers', [\App\Http\Controllers\Api\V1\OfferApiController::class, 'incomingOffers']);
        Route::post('/offers/{id}/accept', [\App\Http\Controllers\Api\V1\OfferApiController::class, 'accept']);
        Route::post('/offers/{id}/reject', [\App\Http\Controllers\Api\V1\OfferApiController::class, 'reject']);

        // Checkout & Orders Endpoints (Buyer)
        Route::get('/checkout/{product}', [\App\Http\Controllers\Api\V1\CheckoutApiController::class, 'show']);
        Route::post('/checkout', [\App\Http\Controllers\Api\V1\CheckoutApiController::class, 'store']);
        Route::get('/orders', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'show']);
        Route::post('/orders/{id}/confirm', [\App\Http\Controllers\Api\V1\OrderApiController::class, 'confirmReceipt']);

        // Chat Endpoints
        Route::get('/chat', [\App\Http\Controllers\Api\V1\ChatApiController::class, 'index']);
        Route::get('/chat/{otherUserId}', [\App\Http\Controllers\Api\V1\ChatApiController::class, 'show']);
        Route::post('/chat', [\App\Http\Controllers\Api\V1\ChatApiController::class, 'store']);

        // Notification Endpoints (Role Decoupled MongoDB)
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'index']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAllAsRead']);

        // Seller Operations Pre-Authorization
        Route::get('/seller/verification', [\App\Http\Controllers\Api\V1\SellerVerificationApiController::class, 'show']);
        Route::post('/seller/verification', [\App\Http\Controllers\Api\V1\SellerVerificationApiController::class, 'store']);

        // Seller Dashboard & Operations Endpoints (Requires Verified Seller Status)
        Route::middleware('seller')->prefix('seller')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\SellerDashboardApiController::class, 'index']);
            
            Route::get('/orders', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'index']);
            Route::post('/orders/{id}/ship', [\App\Http\Controllers\Api\V1\SellerOrderApiController::class, 'ship']);
            
            Route::get('/balance', [\App\Http\Controllers\Api\V1\SellerBalanceApiController::class, 'index']);
            Route::post('/withdrawals', [\App\Http\Controllers\Api\V1\WithdrawalApiController::class, 'store']);
            Route::get('/activities', [\App\Http\Controllers\Api\V1\SellerActivityApiController::class, 'index']);
            
            // Seller Product Management
            Route::get('/settings', [\App\Http\Controllers\Api\V1\SellerSettingsApiController::class, 'show']);
            Route::patch('/settings', [\App\Http\Controllers\Api\V1\SellerSettingsApiController::class, 'update']);

            Route::get('/products', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'index']);
            Route::post('/products', [ProductApiController::class, 'store']); // Use common Product store
            Route::patch('/products/{id}', [ProductApiController::class, 'update']); // Use common Product update
            Route::post('/products/{id}/toggle-archive', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'toggleArchive']);
            Route::delete('/products/{id}', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'destroy']);
            Route::post('/products/{id}/restore', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'restore']);
            Route::delete('/products/{id}/force', [\App\Http\Controllers\Api\V1\SellerProductApiController::class, 'forceDestroy']);
        });

        // Admin Endpoints
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'index']);
            
            // Users Management
            Route::get('/users', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'users']);
            Route::post('/users', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'storeUser']);
            Route::patch('/users/{id}', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'updateUser']);
            Route::delete('/users/{id}', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'destroyUser']);
            Route::post('/users/{id}/toggle-status', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'toggleUserStatus']);
            
            // Admin Products, Offers, Transactions, Activities
            Route::get('/products', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'products']);
            Route::post('/products/{id}/status', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'updateProductStatus']);
            Route::get('/offers', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'offers']);
            Route::get('/transactions', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'transactions']);
            Route::get('/activities', [\App\Http\Controllers\Api\V1\AdminDashboardApiController::class, 'activities']);
            
            // Verifications
            Route::get('/verifications', [\App\Http\Controllers\Api\V1\AdminVerificationApiController::class, 'index']);
            Route::post('/verifications/{id}/approve', [\App\Http\Controllers\Api\V1\AdminVerificationApiController::class, 'approve']);
            Route::post('/verifications/{id}/reject', [\App\Http\Controllers\Api\V1\AdminVerificationApiController::class, 'reject']);
        });

        // Logistics Endpoints
        Route::prefix('logistics')->group(function () {
            Route::get('/destination', [\App\Http\Controllers\Api\V1\LogisticsApiController::class, 'searchDestination']);
            Route::post('/calculate', [\App\Http\Controllers\Api\V1\LogisticsApiController::class, 'calculateCost']);
        });
    }); // End auth:sanctum group
}); // End v1 group
