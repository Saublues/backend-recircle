<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerBalance;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function __construct(
        protected OfferService $offerService
    ) {}

    /**
     * Halaman checkout — bisa dari full price atau dari accepted offer.
     */
    public function show(Request $request, Product $product)
    {
        $offer = null;
        $finalPrice = $product->harga;

        // Cek apakah ada accepted offer milik buyer ini
        if ($request->has('offer_id')) {
            $offer = Offer::where('id', $request->offer_id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'accepted')
                ->where('deal_expired_at', '>', now())
                ->first();

            if ($offer) {
                $finalPrice = $offer->harga_deal;
            }
        }

        return Inertia::render('Checkout', [
            'product' => [
                'id'    => $product->id,
                'name'  => $product->nama_barang,
                'price' => $product->harga,
                'img'   => $product->foto_barang_url,
                'loc'   => $product->lokasi_kampus,
                'seller_name' => $product->user->name,
                'seller_area_id' => 'IDNP10CT66ID', // Biteship Default Valid Area ID
            ],
            'offer' => $offer ? [
                'id'         => $offer->id,
                'harga_deal' => $offer->harga_deal,
                'expired_at' => $offer->deal_expired_at->toISOString(),
            ] : null,
            // final_price di sini untuk initial state. Frontend akan menambahkannya dengan shipping_cost nanti.
            'final_price'       => $finalPrice, 
            'midtrans_client_key' => config('services.midtrans.client_key'),
        ]);
    }

    /**
     * Proses checkout.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'         => 'required|exists:products,id',
            'offer_id'           => 'nullable|exists:offers,id',
            'metode_pengiriman'  => 'required|in:cod,kirim_paket',
            'alamat_pengiriman'  => 'required_if:metode_pengiriman,kirim_paket|nullable|string',
            'catatan'            => 'nullable|string|max:500',
            // Tambahkan validasi ongkir
            'shipping_cost'      => 'nullable|numeric|min:0', 
            // Opsional: simpan info kurir/layanan untuk referensi jika dibutuhkan
            'courier_info'       => 'nullable|string'
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Verifikasi produk masih active
        if (!$product->isActive()) {
            return response()->json(['message' => 'Produk sudah tidak tersedia.'], 400);
        }

        $offer = null;
        if (!empty($validated['offer_id'])) {
            $offer = Offer::where('id', $validated['offer_id'])
                ->where('user_id', $request->user()->id)
                ->where('status', 'accepted')
                ->where('deal_expired_at', '>', now())
                ->first();
        }

        try {
            DB::beginTransaction();

            $subTotal = $offer ? $offer->harga_deal : $product->harga;
            $ongkir = ($validated['metode_pengiriman'] === 'kirim_paket' && isset($validated['shipping_cost'])) 
                      ? (int) $validated['shipping_cost'] 
                      : 0;

            $totalHarga = $subTotal + $ongkir; 
            
            $orderKode = 'RC-' . strtoupper(uniqid()) . '-' . time();

            // Gabungkan info kurir ke catatan atau alamat (asumsi tabel orders tidak punya field 'courier')
            $alamatLengkap = $validated['alamat_pengiriman'] ?? null;
            if ($alamatLengkap && isset($validated['courier_info'])) {
                $alamatLengkap .= " (Kurir: " . $validated['courier_info'] . ")";
            }

            // 1. Buat Order
            $order = Order::create([
                'kode_pesanan'       => $orderKode,
                'user_id'            => $request->user()->id,
                'product_id'         => $product->id,
                'offer_id'           => $offer?->id,
                'total_harga'        => $totalHarga, // Subtotal + Ongkir
                'metode_pengiriman'  => $validated['metode_pengiriman'],
                'status'             => 'pending',
                'alamat_pengiriman'  => $alamatLengkap,
                'catatan'            => $validated['catatan'] ?? null,
                'dana_ditahan'       => $validated['metode_pengiriman'] === 'kirim_paket',
            ]);

            // 2. Tandai produk sebagai sold
            $product->markAsSold();

            // 3. Auto-cancel penawaran lain untuk produk ini
            $this->offerService->cancelAllOffersForProduct($product);

            $snapToken = null;

            // 4. Generate Midtrans Snap Token Jika Kirim Paket
            if ($validated['metode_pengiriman'] === 'kirim_paket') {
                // Setup Midtrans Configuration
                Config::$serverKey = config('services.midtrans.server_key');
                Config::$isProduction = config('services.midtrans.is_production');
                Config::$isSanitized = true;
                Config::$is3ds = true;

                $params = [
                    'transaction_details' => [
                        'order_id'     => $order->kode_pesanan,
                        'gross_amount' => $order->total_harga,
                    ],
                    'customer_details' => [
                        'first_name' => $request->user()->name,
                        'email'      => $request->user()->email,
                        'phone'      => $request->user()->nomor_wa ?? '',
                    ],
                    'item_details' => [
                        [
                            'id'       => $product->id,
                            'price'    => $subTotal,
                            'quantity' => 1,
                            'name'     => substr($product->nama_barang, 0, 50),
                        ],
                    ],
                ];

                // Tambahkan ongkir sebagai item terpisah di Midtrans jika > 0
                if ($ongkir > 0) {
                    $params['item_details'][] = [
                        'id'       => 'shipping_cost',
                        'price'    => $ongkir,
                        'quantity' => 1,
                        'name'     => 'Biaya Pengiriman',
                    ];
                }

                $snapToken = Snap::getSnapToken($params);

                // Update Order with Snap Token
                $order->update([
                    'midtrans_snap_token' => $snapToken,
                ]);

                // Buat record Seller Balance (ditahan)
                SellerBalance::create([
                    'user_id'  => $product->user_id,
                    'order_id' => $order->id,
                    'jumlah'   => $totalHarga,
                    'status'   => 'ditahan',
                ]);
            }

            DB::commit();

            // Return JSON response supaya frontend bisa langsung trigger Snap Modal
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'metode_pengiriman' => $validated['metode_pengiriman'],
                'snap_token' => $snapToken,
                'client_key' => config('services.midtrans.client_key'),
                'redirect_url' => route('orders.index') // Fallback redirect url
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memproses checkout: ' . $e->getMessage()], 500);
        }
    }
}
