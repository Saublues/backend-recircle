<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $carts = Cart::with(['product.user', 'product.images', 'offer'])
            ->where('user_id', $request->user()->id)
            ->get();

        // Group by seller_id
        $groupedCarts = $carts->groupBy(function ($cartItem) {
            return $cartItem->product->user_id;
        })->map(function ($items) {
            $seller = $items->first()->product->user;
            return [
                'seller' => [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'universitas' => $seller->universitas,
                ],
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->nama_barang,
                            'price' => $item->product->harga,
                            'image' => $item->product->foto_barang_url, // Using accessor or generic fallback
                        ],
                        'offer' => $item->offer ? [
                            'id' => $item->offer->id,
                            'harga_deal' => $item->offer->harga_deal,
                            'expires_at' => $item->offer->deal_expired_at,
                        ] : null,
                    ];
                }),
            ];
        })->values();

        return Inertia::render('Cart/Index', [
            'groupedCarts' => $groupedCarts,
        ]);
    }
}
