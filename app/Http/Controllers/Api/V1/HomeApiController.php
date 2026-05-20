<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeApiController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        try {
            // 1. Categories
            $categories = Category::withCount(['products' => function ($query) {
                $query->where('status', 'active');
            }])->get()->map(fn($category) => [
                'id'    => $category->id,
                'name'  => $category->nama_kategori,
                'count' => $category->products_count . '+',
                'icon'  => $category->icon ?? 'Search',
            ]);

            // 2. Recommended/Trending Products
            $query = Product::with(['images', 'user'])
                ->where('status', 'active');

            if (Auth::guard('sanctum')->check()) {
                $userId = Auth::guard('sanctum')->id();
                $preferredCategoryIds = DB::table('carts')
                    ->join('products', 'carts.product_id', '=', 'products.id')
                    ->where('carts.user_id', $userId)
                    ->pluck('products.category_id')
                    ->unique();

                if ($preferredCategoryIds->isNotEmpty()) {
                    $query->whereIn('category_id', $preferredCategoryIds)
                        ->where('user_id', '!=', $userId);
                }
            }

            $products = $query->inRandomOrder()->take(4)->get()->map(fn($p) => [
                'id'     => $p->id,
                'name'   => $p->nama_barang,
                'price'  => $p->harga,
                'loc'    => $p->lokasi_kampus,
                'img'    => $p->foto_barang_url,
                'tag'    => $p->kondisi,
                'seller' => $p->user->name,
            ]);

            return $this->successResponse([
                'categories' => $categories,
                'trending_products' => $products,
            ], 'Home data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve home data: ' . $e->getMessage(), 500);
        }
    }
}
