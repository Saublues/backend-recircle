<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Order;
use App\Models\SellerBalance;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SellerDashboardController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    /**
     * Dashboard utama seller.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $productIds = $user->products()->pluck('id');

        // Statistik
        $totalProducts  = $user->products()->count();
        $activeProducts = $user->products()->where('status', 'active')->count();
        $totalSold      = $user->products()->where('status', 'sold')->count();

        $totalRevenue  = SellerBalance::where('user_id', $user->id)
            ->whereIn('status', ['dilepas', 'ditarik'])
            ->sum('jumlah');
        $pendingBalance = SellerBalance::where('user_id', $user->id)
            ->where('status', 'ditahan')
            ->sum('jumlah');

        // Action center data
        $pendingOffers = Offer::whereIn('product_id', $productIds)
            ->where('status', 'pending')
            ->count();
        $activeOrders  = Order::whereIn('product_id', $productIds)
            ->whereIn('status', ['dibayar', 'dikirim'])
            ->count();
        $needsShipping = Order::whereIn('product_id', $productIds)
            ->where('status', 'dibayar')
            ->count();

        // Recent orders for activity feed
        $recentOrders = Order::whereIn('product_id', $productIds)
            ->with(['user', 'product'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'id'                => $order->id,
                'kode_pesanan'      => $order->kode_pesanan,
                'total_harga'       => $order->total_harga,
                'status'            => $order->status,
                'metode_pengiriman' => $order->metode_pengiriman,
                'created_at'        => $order->created_at->diffForHumans(),
                'buyer_name'        => $order->user->name,
                'product_name'      => $order->product->nama_barang,
                'product_img'       => $order->product->foto_barang_url,
            ]);

        return Inertia::render('Seller/Dashboard', [
            'stats' => [
                'total_products'   => $totalProducts,
                'active_products'  => $activeProducts,
                'total_sold'       => $totalSold,
                'total_revenue'    => $totalRevenue,
                'pending_balance'  => $pendingBalance,
                'pending_offers'   => $pendingOffers,
                'active_orders'    => $activeOrders,
                'needs_shipping'   => $needsShipping,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Daftar penawaran masuk untuk produk seller.
     */
    public function offers(Request $request)
    {
        $productIds = $request->user()->products()->pluck('id');

        $offers = Offer::whereIn('product_id', $productIds)
            ->with(['user', 'product'])
            ->latest()
            ->get()
            ->map(fn ($offer) => [
                'id'          => $offer->id,
                'harga_tawar' => $offer->harga_tawar,
                'harga_deal'  => $offer->harga_deal,
                'pesan'       => $offer->pesan,
                'status'      => $offer->status,
                'expired_at'  => $offer->deal_expired_at?->toISOString(),
                'created_at'  => $offer->created_at->diffForHumans(),
                'buyer' => [
                    'name'   => $offer->user->name,
                    'avatar' => $offer->user->avatar
                        ?? 'https://ui-avatars.com/api/?name=' . urlencode($offer->user->name) . '&background=43552c&color=fff',
                ],
                'product' => [
                    'id'    => $offer->product->id,
                    'name'  => $offer->product->nama_barang,
                    'price' => $offer->product->harga,
                    'img'   => $offer->product->foto_barang_url,
                ],
            ]);

        return Inertia::render('Seller/Offers', [
            'offers' => $offers,
        ]);
    }

    /**
     * Daftar pesanan masuk (produk seller yang dibeli orang).
     */
    public function orders(Request $request)
    {
        $productIds = $request->user()->products()->pluck('id');

        $orders = Order::whereIn('product_id', $productIds)
            ->with(['user', 'product'])
            ->latest()
            ->get()
            ->map(fn ($order) => [
                'id'                => $order->id,
                'kode_pesanan'      => $order->kode_pesanan,
                'total_harga'       => $order->total_harga,
                'metode_pengiriman' => $order->metode_pengiriman,
                'status'            => $order->status,
                'alamat_pengiriman' => $order->alamat_pengiriman,
                'created_at'        => $order->created_at->diffForHumans(),
                'buyer' => [
                    'name' => $order->user->name,
                ],
                'product' => [
                    'name' => $order->product->nama_barang,
                    'img'  => $order->product->foto_barang_url,
                ],
            ]);

        return Inertia::render('Seller/Orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Seller menandai barang dikirim.
     */
    public function shipOrder(Order $order)
    {
        $productIds = auth()->user()->products()->pluck('id');
        if (!$productIds->contains($order->product_id)) {
            abort(403);
        }

        if ($order->status !== 'dibayar') {
            return back()->withErrors(['order' => 'Order belum dibayar.']);
        }

        $this->orderService->markAsShipped($order);
        return back()->with('success', 'Barang ditandai sudah dikirim.');
    }

    /**
     * Seller: Lihat saldo/balance.
     */
    public function balance(Request $request)
    {
        $balances = SellerBalance::where('user_id', $request->user()->id)
            ->with('order.product')
            ->latest()
            ->get()
            ->map(fn ($b) => [
                'id'            => $b->id,
                'jumlah'        => $b->jumlah,
                'status'        => $b->status,
                'dilepas_at'    => $b->dilepas_at?->diffForHumans(),
                'created_at'    => $b->created_at->diffForHumans(),
                'kode_pesanan'  => $b->order->kode_pesanan,
                'product_name'  => $b->order->product->nama_barang,
            ]);

        $totalDilepas = SellerBalance::where('user_id', $request->user()->id)
            ->whereIn('status', ['dilepas', 'ditarik'])
            ->sum('jumlah');

        return Inertia::render('Seller/Balance', [
            'balances'      => $balances,
            'total_dilepas' => $totalDilepas,
        ]);
    }

    /**
     * Seller: Kelola Produk (List Produk Sendiri).
     */
    public function products(Request $request)
    {
        $products = $request->user()->products()
            ->with(['category', 'orders' => function($q) {
                $q->whereIn('status', ['pending', 'dibayar', 'dikirim']);
            }])
            ->latest()
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'name'          => $p->nama_barang,
                'price'         => $p->harga,
                'category'      => $p->category->nama_kategori ?? '-',
                'status'        => $p->status,
                'img'           => $p->foto_barang_url,
                'created_at'    => $p->created_at->diffForHumans(),
                'active_orders' => $p->orders->count(),
            ]);

        return Inertia::render('Seller/Products', [
            'products' => $products,
        ]);
    }

    /**
     * Halaman Edit Produk.
     */
    public function editProduct(Request $request, \App\Models\Product $product)
    {
        if ($product->user_id !== $request->user()->id) abort(403);

        $categories = \App\Models\Category::all()->map(fn ($c) => [
            'id'   => $c->id,
            'name' => $c->nama_kategori,
        ]);

        return Inertia::render('Seller/EditProduct', [
            'product' => [
                'id'            => $product->id,
                'nama_barang'   => $product->nama_barang,
                'harga'         => $product->harga,
                'category_id'   => $product->category_id,
                'kondisi'       => $product->kondisi,
                'deskripsi'     => $product->deskripsi,
                'status'        => $product->status,
                'image'         => $product->image,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Update Produk.
     */
    public function updateProduct(Request $request, \App\Models\Product $product)
    {
        if ($product->user_id !== $request->user()->id) abort(403);

        $validated = $request->validate([
            'nama_barang'   => 'required|string|max:255',
            'harga'         => 'required|numeric|min:1000',
            'category_id'   => 'required|exists:categories,id',
            'kondisi'       => 'required|string',
            'deskripsi'     => 'required|string',
            'status'        => 'required|in:active,sold,archived',
            'image'         => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $data = [
            'nama_barang'   => $validated['nama_barang'],
            'harga'         => $validated['harga'],
            'category_id'   => $validated['category_id'],
            'kondisi'       => $validated['kondisi'],
            'deskripsi'     => $validated['deskripsi'],
            'status'        => $validated['status'],
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $product->update($data);

        return redirect()->route('seller.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    /**
     * Delete Produk (Change status to archived / soft delete).
     */
    public function destroyProduct(Request $request, \App\Models\Product $product)
    {
        if ($product->user_id !== $request->user()->id) abort(403);

        // Jika ada order aktif, ga boleh dihapus/archived? (Opsional, kita cukup ubah status dan hide di public)
        $product->update(['status' => 'archived']);

        return back()->with('success', 'Produk berhasil di-Takedown (diubah ke Archived).');
    }

    /**
     * Halaman List Semua Aktivitas Seller.
     */
    public function activities(Request $request)
    {
        $activities = \App\Services\ActivityService::getSellerActivities($request->user()->id, 20);

        return Inertia::render('Seller/Activities', [
            'activities' => $activities,
        ]);
    }
}
