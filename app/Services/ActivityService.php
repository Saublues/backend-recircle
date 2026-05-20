<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Offer;

class ActivityService
{
    public static function getRecentActivities($limit = 5, $type = null)
    {
        $subLimit = max(1, (int) ($limit / 2));

        $users = User::latest()
            ->when($type && $type !== 'all', function ($query) use ($type) {
                return $type === 'user' ? $query : $query->whereRaw('1 = 0');
            })
            ->limit($type === 'user' ? $limit : $subLimit)
            ->get()
            ->map(fn($u) => [
                'type' => 'user',
                'title' => 'User Baru terdaftar',
                'description' => "{$u->name} bergabung sebagai " . ($u->role ?? 'user'),
                'time' => $u->created_at->diffForHumans(),
                'timestamp' => $u->created_at->toIso8601String(),
            ]);

        $products = Product::latest()
            ->when($type && $type !== 'all', function ($query) use ($type) {
                return $type === 'product' ? $query : $query->whereRaw('1 = 0');
            })
            ->limit($type === 'product' ? $limit : $subLimit)
            ->get()
            ->map(fn($p) => [
                'type' => 'product',
                'title' => 'Produk Baru diunggah',
                'description' => "{$p->nama_barang} diunggah oleh {$p->user->name}",
                'time' => $p->created_at->diffForHumans(),
                'timestamp' => $p->created_at->toIso8601String(),
            ]);

        $orders = Order::latest()
            ->when($type && $type !== 'all', function ($query) use ($type) {
                return $type === 'order' ? $query : $query->whereRaw('1 = 0');
            })
            ->limit($type === 'order' ? $limit : $subLimit)
            ->get()
            ->map(fn($o) => [
                'type' => 'order',
                'title' => 'Pesanan Baru',
                'description' => "Pesanan #{$o->id} senilai Rp " . number_format($o->total_harga, 0, ',', '.'),
                'time' => $o->created_at->diffForHumans(),
                'timestamp' => $o->created_at->toIso8601String(),
            ]);

        return $users->concat($products)->concat($orders)
            ->sortByDesc('timestamp')
            ->values()
            ->take($limit);
    }

    public static function getSellerActivities($userId, $limit = 5, $type = null)
    {
        $seller = User::find($userId);
        if (!$seller)
            return collect();

        $productIds = $seller->products()->pluck('id');

        $offers = Offer::whereIn('product_id', $productIds)
            ->when($type && $type !== 'all', function ($query) use ($type) {
                return $type === 'offer' ? $query : $query->whereRaw('1 = 0');
            })
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($o) => [
                'type' => 'offer',
                'title' => 'Penawaran Baru',
                'description' => "Penawaran Rp " . number_format($o->harga_tawar, 0, ',', '.') . " untuk {$o->product->nama_barang}",
                'time' => $o->created_at->diffForHumans(),
                'timestamp' => $o->created_at->toIso8601String(),
            ]);

        $orders = Order::whereIn('product_id', $productIds)
            ->when($type && $type !== 'all', function ($query) use ($type) {
                return $type === 'order' ? $query : $query->whereRaw('1 = 0');
            })
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($o) => [
                'type' => 'order',
                'title' => 'Pesanan Baru',
                'description' => "Pesanan #{$o->id} untuk {$o->product->nama_barang}",
                'time' => $o->created_at->diffForHumans(),
                'timestamp' => $o->created_at->toIso8601String(),
            ]);

        return $offers->concat($orders)
            ->sortByDesc('timestamp')
            ->values()
            ->take($limit);
    }
}
