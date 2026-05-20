<?php

use Illuminate\Support\Facades\Route;

// ── Fallback Route untuk SPA ──────────────────────────────────
// API-First Architecture. All API routes are in routes/api.php
// This web.php now only serves fallback or authentication routes.

Route::get('/', function () {
    return response()->json([
        'app' => 'ReCircle API',
        'version' => '1.0',
        'status' => 'running',
    ]);
});

// Autentikasi Sanctum SPA diletakkan di auth.php
require __DIR__ . '/auth.php';

// <?php

// use App\Http\Controllers\ProfileController;
// use App\Http\Controllers\ProductController;
// use App\Http\Controllers\OfferController;
// use App\Http\Controllers\CheckoutController;
// use App\Http\Controllers\OrderController;
// use App\Http\Controllers\SellerDashboardController;
// use App\Http\Controllers\SellerVerificationController;
// use App\Http\Controllers\AdminVerificationController;
// use App\Http\Controllers\AdminDashboardController;
// use App\Http\Controllers\ChatController;
// use App\Http\Controllers\CartController;
// use App\Http\Controllers\RajaOngkirController;
// use App\Http\Controllers\WishlistController;
// use App\Http\Controllers\SellerProductController;
// use App\Services\RecommendationService;
// use Illuminate\Support\Facades\Route;
// use Inertia\Inertia;

// // ── Public Routes ──────────────────────────────────

// Route::get('/', function (RecommendationService $recommendationService) {
//     $user = auth()->user();
//     $recommendations = $recommendationService->getRecommendations($user);

//     $categories = \App\Models\Category::withCount(['products' => fn($q) => $q->where('status', 'active')])->get();

//     return Inertia::render('Home', [
//         'recommendations' => $recommendations->map(fn($p) => [
//             'id' => $p->id,
//             'name' => $p->nama_barang,
//             'price' => $p->harga,
//             'loc' => $p->lokasi_kampus,
//             'img' => $p->image,
//             'tag' => null,
//             'seller' => $p->user->name,
//         ]),
//         'categories' => $categories->map(fn($c) => [
//             'id' => $c->id,
//             'name' => $c->nama_kategori,
//             'slug' => $c->slug,
//             'count' => $c->products_count . '+',
//         ]),
//     ]);
// });

// Route::get('/product/{id}', [ProductController::class, 'show'])->name('product.show');
// Route::get('/explore', [ProductController::class, 'index'])->name('product.index');

// // ── Auth Required Routes ───────────────────────────

// Route::middleware('auth')->group(function () {
//     // Profile
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

//     // Jual Barang (semua user yang login bisa buat, tapi harus seller untuk akses dashboard)
//     Route::get('/jual', [ProductController::class, 'create'])->name('product.create')->middleware('seller');
//     Route::post('/jual', [ProductController::class, 'store'])->name('product.store')->middleware('seller');

//     // Offers (Buyer)
//     Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
//     Route::get('/my-offers', [OfferController::class, 'myOffers'])->name('offers.index');

//     // Cart
//     Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

//     // Wishlist
//     Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
//     Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

//     // Offers (Seller Actions)
//     Route::post('/offers/{offer}/accept', [OfferController::class, 'accept'])->name('offers.accept');
//     Route::post('/offers/{offer}/reject', [OfferController::class, 'reject'])->name('offers.reject');

//     // Checkout & Orders (Buyer)
//     Route::get('/checkout/{product}', [CheckoutController::class, 'show'])->name('checkout.show');
//     Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
//     Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
//     Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
//     Route::post('/orders/{order}/confirm', [OrderController::class, 'confirmReceipt'])->name('orders.confirm');

//     // Seller Verification
//     Route::get('/seller/verification', [SellerVerificationController::class, 'create'])->name('seller.verification.create');
//     Route::post('/seller/verification', [SellerVerificationController::class, 'store'])->name('seller.verification.store');

//     // Chat System
//     Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
//     Route::get('/chat/{user}', [ChatController::class, 'show'])->name('chat.show');
//     Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');

//     // Seller Dashboard (requires verified seller)
//     Route::middleware('seller')->prefix('seller')->name('seller.')->group(function () {
//         Route::get('/dashboard', [SellerDashboardController::class, 'index'])->name('dashboard');
//         Route::get('/offers', [SellerDashboardController::class, 'offers'])->name('offers');
//         Route::get('/orders', [SellerDashboardController::class, 'orders'])->name('orders');
//         Route::post('/orders/{order}/ship', [SellerDashboardController::class, 'shipOrder'])->name('orders.ship');
//         Route::get('/balance', [SellerDashboardController::class, 'balance'])->name('balance');

//         // Product Management
//         Route::get('/products', [SellerProductController::class, 'index'])->name('products.index');
//         Route::get('/products/{product}/edit', [SellerDashboardController::class, 'editProduct'])->name('products.edit');
//         Route::put('/products/{product}', [SellerDashboardController::class, 'updateProduct'])->name('products.update');
//         Route::post('/products/{product}/toggle-archive', [SellerProductController::class, 'toggleArchive'])->name('products.toggleArchive');
//         Route::delete('/products/{product}', [SellerProductController::class, 'destroy'])->name('products.destroy');
//         Route::post('/products/{id}/restore', [SellerProductController::class, 'restore'])->name('products.restore');
//         Route::delete('/products/{id}/force', [SellerProductController::class, 'forceDestroy'])->name('products.forceDestroy');

//         Route::get('/activities', [SellerDashboardController::class, 'activities'])->name('activities');
//     });

//     // Admin Routes
//     Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
//         Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
//         Route::get('/users', [AdminDashboardController::class, 'users'])->name('users.index');
//         Route::post('/users', [AdminDashboardController::class, 'storeUser'])->name('users.store');
//         Route::patch('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
//         Route::delete('/users/{user}', [AdminDashboardController::class, 'destroyUser'])->name('users.destroy');
//         Route::post('/users/{user}/toggle-status', [AdminDashboardController::class, 'toggleUserStatus'])->name('users.toggle-status');
//         Route::get('/products', [AdminDashboardController::class, 'products'])->name('products.index');
//         Route::post('/products/{product}/status', [AdminDashboardController::class, 'updateProductStatus'])->name('products.update-status');
//         Route::get('/offers', [AdminDashboardController::class, 'offers'])->name('offers.index');
//         Route::get('/transactions', [AdminDashboardController::class, 'transactions'])->name('transactions.index');
//         Route::get('/activities', [AdminDashboardController::class, 'activities'])->name('activities.index');
//         Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
//         Route::post('/verifications/{verification}/approve', [AdminVerificationController::class, 'approve'])->name('verifications.approve');
//         Route::post('/verifications/{verification}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');
//     });

//     // Dashboard (generic)
//     Route::get('/dashboard', function () {
//         $user = auth()->user();
//         if ($user->isAdmin()) {
//             return redirect()->route('admin.dashboard');
//         }
//         if ($user->isSeller()) {
//             return redirect()->route('seller.dashboard');
//         }
//         return Inertia::render('Dashboard');
//     })->name('dashboard');

//     // RajaOngkir Komerce V2 Logistics Integration
//     Route::prefix('api/rajaongkir')->group(function () {
//         Route::get('/destination', [RajaOngkirController::class, 'searchDestination']);
//         Route::post('/calculate', [RajaOngkirController::class, 'calculateCost']);
//     });
// });

// require __DIR__ . '/auth.php';
