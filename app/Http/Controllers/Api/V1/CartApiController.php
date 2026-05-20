<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartApiController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the user's cart grouped by seller.
     * Cart = Wishlist items + Accepted (non-expired) Offer items
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            // 1. Get product IDs from Wishlist pivot table
            $wishlistedProductIds = DB::table('wishlists')
                ->where('user_id', $userId)
                ->pluck('product_id');

            // 2. Get accepted & non-expired offers for this buyer
            $acceptedOffers = \App\Models\Offer::where('user_id', $userId)
                ->where('status', 'accepted')
                ->where('deal_expired_at', '>', now())
                ->get();
            $acceptedOfferProductIds = $acceptedOffers->pluck('product_id');

            // 3. Combine unique product IDs (wishlist + accepted offers)
            $allProductIds = $wishlistedProductIds->merge($acceptedOfferProductIds)->unique()->values();

            if ($allProductIds->isEmpty()) {
                return $this->successResponse(collect([]), 'Cart is empty');
            }

            // 4. Fetch Products with seller & images
            $products = \App\Models\Product::with(['user', 'images'])
                ->whereIn('id', $allProductIds)
                ->get();

            // 5. Build Cart Items array
            $cartItems = $products->map(function ($product) use ($acceptedOffers) {
                $offer = $acceptedOffers->firstWhere('product_id', $product->id);

                // Resolve product image
                $image = null;
                if (!empty($product->foto_barang_url)) {
                    $image = $product->foto_barang_url;
                } elseif ($product->images && $product->images->isNotEmpty()) {
                    $image = $product->images->first()->image_url ?? null;
                }

                return [
                    'id'      => $product->id,
                    'product' => [
                        'id'    => $product->id,
                        'name'  => $product->nama_barang,
                        'price' => (int) $product->harga,
                        'image' => $image,
                    ],
                    'offer'  => $offer ? [
                        'id'         => $offer->id,
                        'harga_deal' => (int) $offer->harga_deal,
                        'expires_at' => $offer->deal_expired_at,
                    ] : null,
                    'seller' => $product->user,
                ];
            });

            // 6. Group by Seller ID
            $groupedCarts = $cartItems->groupBy(function ($item) {
                return $item['seller']->id;
            })->map(function ($items) {
                $seller = $items->first()['seller'];
                return [
                    'seller' => [
                        'id'          => $seller->id,
                        'name'        => $seller->name,
                        'universitas' => $seller->kampus ?? $seller->universitas ?? null,
                    ],
                    'items' => collect($items)->map(function ($i) {
                        unset($i['seller']);
                        return $i;
                    })->values(),
                ];
            })->values();

            return $this->successResponse($groupedCarts, 'Cart retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve cart: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add a product to the wishlist/cart.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
            ]);

            $userId    = $request->user()->id;
            $productId = $request->product_id;

            $exists = DB::table('wishlists')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->exists();

            if (!$exists) {
                DB::table('wishlists')->insert([
                    'user_id'    => $userId,
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $this->successResponse(null, 'Berhasil dimasukkan ke keranjang', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add to cart: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified product from the cart.
     * $id = product_id
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $userId    = $request->user()->id;
            $productId = $id;

            // Remove from wishlist pivot table
            DB::table('wishlists')
                ->where('user_id', $userId)
                ->where('product_id', $productId)
                ->delete();

            // Also cancel any pending/accepted offers for this product by this buyer
            \App\Models\Offer::where('user_id', $userId)
                ->where('product_id', $productId)
                ->whereIn('status', ['pending', 'accepted'])
                ->update(['status' => 'cancelled']);

            return $this->successResponse(null, 'Berhasil dihapus dari keranjang');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete cart item: ' . $e->getMessage(), 500);
        }
    }
}
