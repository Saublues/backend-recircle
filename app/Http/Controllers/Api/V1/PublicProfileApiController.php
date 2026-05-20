<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PublicProfileApiController extends Controller
{
    use ApiResponse;

    /**
     * Display public profile of a user and their active products.
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Fetch active products of this user
            $products = $user->products()
                ->active()
                ->with('category')
                ->latest()
                ->get()
                ->map(function ($product) {
                    $images = is_string($product->image) ? json_decode($product->image, true) : $product->image;
                    $imageUrl = null;
                    if (is_array($images) && count($images) > 0) {
                        $imageUrl = filter_var($images[0], FILTER_VALIDATE_URL) ? $images[0] : asset('storage/' . $images[0]);
                    }

                    return [
                        'id' => $product->id,
                        'name' => $product->nama_barang,
                        'price' => $product->harga,
                        'condition' => $product->kondisi,
                        'image' => $imageUrl,
                        'category' => $product->category ? $product->category->name : null,
                        'created_at' => $product->created_at,
                    ];
                });

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'kampus' => $user->kampus,
                'bio' => $user->bio,
                'avatar' => $user->avatar_url,
                'is_verified' => $user->is_seller,
                'joined_at' => $user->created_at,
                'rating' => 5, // TODO: Calculate from reviews table when available
                'reviews_count' => 0, // TODO: Calculate from reviews table when available
                'products' => $products,
            ];

            return $this->successResponse($data, 'Public profile retrieved successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('User not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profile', 500);
        }
    }
}
