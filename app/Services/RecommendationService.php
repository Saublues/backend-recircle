<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\UserCategoryView;

class RecommendationService
{
    /**
     * Ambil produk rekomendasi untuk homepage.
     * Untuk user login: berdasarkan top 3 kategori yang sering dilihat.
     * Untuk guest: produk terbaru.
     */
    public function getRecommendations(?User $user, int $limit = 8)
    {
        if ($user) {
            return $this->getPersonalizedRecommendations($user, $limit);
        }

        return $this->getDefaultRecommendations($limit);
    }

    /**
     * Rekam event view: user melihat produk dari kategori tertentu.
     * Increment jumlah_view jika sudah ada, create baru jika belum.
     */
    public function recordView(User $user, Product $product): void
    {
        UserCategoryView::updateOrCreate(
            [
                'user_id'     => $user->id,
                'category_id' => $product->category_id,
            ],
            [] // Kolom yang dicari/dibuat
        )->increment('jumlah_view');
    }

    /**
     * Rekomendasi berdasarkan preferensi user.
     */
    protected function getPersonalizedRecommendations(User $user, int $limit)
    {
        // Ambil top 3 kategori yang paling sering dilihat user
        $topCategoryIds = UserCategoryView::where('user_id', $user->id)
            ->orderByDesc('jumlah_view')
            ->limit(3)
            ->pluck('category_id');

        if ($topCategoryIds->isEmpty()) {
            return $this->getDefaultRecommendations($limit);
        }

        // Ambil produk dari kategori favorit, kecuali produk milik user sendiri
        $products = Product::active()
            ->whereIn('category_id', $topCategoryIds)
            ->where('user_id', '!=', $user->id)
            ->with(['user', 'category', 'images'])
            ->latest()
            ->limit($limit)
            ->get();

        // Jika kurang dari limit, tambahkan produk terbaru dari kategori lain
        if ($products->count() < $limit) {
            $remaining = $limit - $products->count();
            $existingIds = $products->pluck('id');

            $extraProducts = Product::active()
                ->whereNotIn('id', $existingIds)
                ->where('user_id', '!=', $user->id)
                ->with(['user', 'category', 'images'])
                ->latest()
                ->limit($remaining)
                ->get();

            $products = $products->merge($extraProducts);
        }

        return $products;
    }

    /**
     * Rekomendasi default: produk terbaru.
     */
    protected function getDefaultRecommendations(int $limit)
    {
        return Product::active()
            ->with(['user', 'category', 'images'])
            ->latest()
            ->limit($limit)
            ->get();
    }
}
