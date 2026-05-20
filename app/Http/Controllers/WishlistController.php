<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WishlistController extends Controller
{
    /**
     * Tampilkan halaman daftar Wishlist.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Eager load category dan seller untuk mencegah N+1
        $wishlists = $user->wishlists()
            ->with(['category', 'user' => function($q) {
                // select ID dan Name saja dari seller
                $q->select('id', 'name', 'avatar', 'is_verified', 'kampus');
            }])
            ->get();

        // Format data untuk frontend agar kompatibel
        $formattedWishlists = $wishlists->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->nama_barang,
                'price' => $product->harga,
                'loc' => $product->lokasi_kampus,
                'img' => $product->image,
                'category' => $product->category->nama_kategori ?? 'Umum',
                'seller' => [
                    'id' => $product->user->id,
                    'name' => $product->user->name,
                ],
                'status' => $product->status, // misal: active, sold
                'is_wishlisted' => true, // Selalu true di halaman wishlist
            ];
        });

        // Ambil kategori unik yang ada di wishlist pengguna untuk filter Chip
        $categories = $wishlists->pluck('category.nama_kategori')
            ->filter()
            ->unique()
            ->values();

        return Inertia::render('Wishlist/Index', [
            'wishlists' => $formattedWishlists,
            'categories' => $categories
        ]);
    }

    /**
     * Toggle status Wishlist (Tambah/Hapus).
     */
    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();

        // Menggunakan syncWithoutDetaching atau toggle()
        // toggle() mengembalikan array berisi ID yang di-'attached' dan di-'detached'
        $toggled = $user->wishlists()->toggle($product->id);

        $isAttached = count($toggled['attached']) > 0;

        // Redirect back secara default untuk Inertia, page props list id akan langsung up-to-date!
        return back()->with('success', $isAttached ? 'Ditambahkan ke Wishlist' : 'Dihapus dari Wishlist');
    }
}
