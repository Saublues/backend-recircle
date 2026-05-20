<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil Data Kategori & Hitung Jumlah Produk Aktif
        $categories = Category::withCount([
            'products' => function ($query) {
                $query->where('status', 'active');
            }
        ])->get()->map(function ($category) {
            return [
                'name' => $category->nama_kategori,
                'count' => $category->products_count . '+', // Format sesuai frontend
                'icon' => $category->icon ?? 'Search', // Fallback icon
            ];
        });

        // 2. ALGORITMA REKOMENDASI (Based on User Preferences)
        $trendingProductsQuery = Product::with(['images', 'user'])
            ->where('status', 'active');

        // Jika user login, cari tahu preferensi mereka berdasarkan isi keranjang
        if (Auth::check()) {
            $userId = Auth::id();

            $preferredCategoryIds = DB::table('carts')
                ->join('products', 'carts.product_id', '=', 'products.id')
                ->where('carts.user_id', $userId)
                ->pluck('products.category_id')
                ->unique();

            if ($preferredCategoryIds->isNotEmpty()) {
                $trendingProductsQuery->whereIn('category_id', $preferredCategoryIds)
                    ->where('user_id', '!=', $userId);
            }
        }

        $trendingProducts = $trendingProductsQuery
            ->inRandomOrder()
            ->take(4)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->nama_barang,
                    'price' => $product->harga,
                    'loc' => $product->lokasi_kampus,
                    'img' => $product->foto_barang_url,
                    'tag' => $product->kondisi,
                    'seller' => $product->user->name,
                ];
            });

        // 3. Kirim ke Frontend (Inertia)
        return Inertia::render('Home', [
            'categories' => $categories,
            'trendingProducts' => $trendingProducts
        ]);
    }
}