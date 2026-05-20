<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerBalance;
use App\Services\OfferService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected OfferService $offerService) {}

    public function show(Product $product, Request $request): JsonResponse
    {
        try {
            $offer = null;
            $finalPrice = $product->harga;

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

            return $this->successResponse([
                'product' => [
                    'id'    => $product->id,
                    'name'  => $product->nama_barang,
                    'price' => $product->harga,
                    'img'   => $product->foto_barang_url,
                    'loc'   => $product->lokasi_kampus,
                    'seller_name' => $product->user->name,
                    'seller_area_id' => $product->user->komerce_destination_id ?? '34260',
                ],
                'offer' => $offer,
                'final_price' => $finalPrice,
                'midtrans_client_key' => config('services.midtrans.client_key'),
            ], 'Checkout data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve checkout data', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id'         => 'required|exists:products,id',
                'offer_id'           => 'nullable|exists:offers,id',
                'metode_pengiriman'  => 'required|in:cod,kirim_paket',
                'alamat_pengiriman'  => 'required_if:metode_pengiriman,kirim_paket|nullable|string',
                'catatan'            => 'nullable|string|max:500',
                'shipping_cost'      => 'nullable|numeric|min:0',
                'courier_info'       => 'nullable|string'
            ]);

            $product = Product::findOrFail($validated['product_id']);

            if (!$product->isActive()) {
                return $this->errorResponse('Product is no longer available', 400);
            }

            $offer = null;
            if (!empty($validated['offer_id'])) {
                $offer = Offer::where('id', $validated['offer_id'])
                    ->where('user_id', $request->user()->id)
                    ->where('status', 'accepted')
                    ->where('deal_expired_at', '>', now())
                    ->first();
            }

            DB::beginTransaction();

            $subTotal = $offer ? $offer->harga_deal : $product->harga;
            $ongkir = ($validated['metode_pengiriman'] === 'kirim_paket' && isset($validated['shipping_cost'])) 
                      ? (int) $validated['shipping_cost'] 
                      : 0;

            $totalHarga = $subTotal + $ongkir;
            $orderKode = 'RC-' . strtoupper(uniqid()) . '-' . time();

            $alamatLengkap = $validated['alamat_pengiriman'] ?? null;
            if ($alamatLengkap && isset($validated['courier_info'])) {
                $alamatLengkap .= " (Kurir: " . $validated['courier_info'] . ")";
            }

            $order = Order::create([
                'kode_pesanan'       => $orderKode,
                'user_id'            => $request->user()->id,
                'product_id'         => $product->id,
                'offer_id'           => $offer?->id,
                'total_harga'        => $totalHarga,
                'metode_pengiriman'  => $validated['metode_pengiriman'],
                'status'             => 'pending',
                'alamat_pengiriman'  => $alamatLengkap,
                'catatan'            => $validated['catatan'] ?? null,
                'dana_ditahan'       => $validated['metode_pengiriman'] === 'kirim_paket',
            ]);

            $product->markAsSold();
            $this->offerService->cancelAllOffersForProduct($product);

            $snapToken = null;

            if ($validated['metode_pengiriman'] === 'kirim_paket') {
                Config::$serverKey = config('services.midtrans.server_key');
                Config::$isProduction = config('services.midtrans.is_production');
                Config::$isSanitized = true;
                Config::$is3ds = true;

                $params = [
                    'transaction_details' => [
                        'order_id'     => $order->kode_pesanan,
                        'gross_amount' => (int) $order->total_harga,
                    ],
                    'customer_details' => [
                        'first_name' => $request->user()->name,
                        'email'      => $request->user()->email,
                        'phone'      => $request->user()->nomor_wa ?? '',
                    ],
                    'item_details' => [
                        [
                            'id'       => $product->id,
                            'price'    => (int) $subTotal,
                            'quantity' => 1,
                            'name'     => substr($product->nama_barang, 0, 50),
                        ],
                    ],
                ];

                if ($ongkir > 0) {
                    $params['item_details'][] = [
                        'id'       => 'shipping_cost',
                        'price'    => (int) $ongkir,
                        'quantity' => 1,
                        'name'     => 'Shipping Cost',
                    ];
                }

                $snapToken = Snap::getSnapToken($params);
                $order->update(['midtrans_snap_token' => $snapToken]);

                SellerBalance::create([
                    'user_id'  => $product->user_id,
                    'order_id' => $order->id,
                    'jumlah'   => $totalHarga,
                    'status'   => 'ditahan',
                ]);
            }

            DB::commit();

            return $this->successResponse([
                'order_id' => $order->id,
                'snap_token' => $snapToken,
            ], 'Checkout processed successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Checkout failed: ' . $e->getMessage(), 500);
        }
    }
}
