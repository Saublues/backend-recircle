<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
    ) {}

    /**
     * Buyer: Riwayat pesanan saya.
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['product.images', 'product.user', 'offer'])
            ->latest()
            ->get()
            ->map(fn ($order) => [
                'id'                => $order->id,
                'kode_pesanan'      => $order->kode_pesanan,
                'total_harga'       => $order->total_harga,
                'metode_pengiriman' => $order->metode_pengiriman,
                'status'            => $order->status,
                'metode_pembayaran' => $order->metode_pembayaran,
                'created_at'        => $order->created_at->diffForHumans(),
                'product' => [
                    'id'    => $order->product->id,
                    'name'  => $order->product->nama_barang,
                    'img'   => $order->product->foto_barang_url,
                    'seller_name' => $order->product->user->name,
                ],
            ]);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Buyer: Konfirmasi terima barang.
     */
    public function confirmReceipt(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        if ($order->status !== 'dikirim') {
            return back()->withErrors(['order' => 'Order belum dikirim.']);
        }

        $this->orderService->confirmReceipt($order);
        return back()->with('success', 'Barang dikonfirmasi diterima. Dana dilepas ke penjual.');
    }
    public function show(Order $order)
    {
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['product.user', 'offer']);

        return Inertia::render('Orders/Show', [
            'order' => [
                'id'                => $order->id,
                'kode_pesanan'      => $order->kode_pesanan,
                'total_harga'       => $order->total_harga,
                'metode_pengiriman' => $order->metode_pengiriman,
                'status'            => $order->status,
                'metode_pembayaran' => $order->metode_pembayaran,
                'alamat_pengiriman' => $order->alamat_pengiriman,
                'catatan'           => $order->catatan,
                'created_at'        => $order->created_at->format('d M Y, H:i'),
                'is_pending'        => $order->isPending(),
            ],
            'product' => [
                'id'          => $order->product->id,
                'name'        => $order->product->nama_barang,
                'price'       => $order->product->harga,
                'img'         => $order->product->foto_barang_url,
                'seller_name' => $order->product->user->name,
                'seller_kampus' => $order->product->user->kampus,
            ],
            // Only pass Snap Token if pending and kirim paket
            'snap_token'          => ($order->isPending() && $order->isKirimPaket()) ? $order->midtrans_snap_token : null,
            'midtrans_client_key' => config('services.midtrans.client_key'),
        ]);
    }
}
