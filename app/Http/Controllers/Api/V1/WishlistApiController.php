<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WishlistResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistApiController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the user's wishlist.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Eager load related products, along with user and category for the resource
            $wishlists = $user->wishlists()
                ->with(['user', 'category'])
                ->latest('wishlists.created_at')
                ->get();

            return $this->successResponse(
                WishlistResource::collection($wishlists),
                'Wishlist retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve wishlist', 500);
        }
    }

    /**
     * Toggle a product in the user's wishlist.
     */
    public function toggle(Request $request, int $productId): JsonResponse
    {
        try {
            $user = $request->user();
            $wishlists = $user->wishlists();

            if ($wishlists->where('product_id', $productId)->exists()) {
                $wishlists->detach($productId);
                return $this->successResponse(null, 'Berhasil dihapus dari wishlist');
            } else {
                $wishlists->attach($productId);
                return $this->successResponse(null, 'Berhasil ditambahkan ke wishlist');
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle wishlist', 500);
        }
    }
}
