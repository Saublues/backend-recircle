<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Offer;
use App\Models\SellerBalance;
use App\Models\User;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected OfferService $offerService,
        protected PaymentGatewayInterface $paymentGateway,
    ) {}

    /**
     * Buat order baru.
     *
     * @return array{order: Order, snap_token: string|null}
     */
    public function createOrder(
        User    $user,
        Product $product,
        string  $metodePengiriman,
        ?Offer  $offer = null,
        ?string $alamat = null,
        ?string $catatan = null,
    ): array {
        return DB::transaction(function () use ($user, $product, $metodePengiriman, $offer, $alamat, $catatan) {
            // Tentukan harga final
            $totalHarga = $offer?->harga_deal ?? $product->harga;

            // Buat order
            $order = Order::create([
                'user_id'            => $user->id,
                'product_id'         => $product->id,
                'offer_id'           => $offer?->id,
                'total_harga'        => $totalHarga,
                'metode_pengiriman'  => $metodePengiriman,
                'status'             => 'pending',
                'alamat_pengiriman'  => $alamat,
                'catatan'            => $catatan,
                'dana_ditahan'       => $metodePengiriman === 'kirim_paket',
            ]);

            // Tandai produk sebagai sold
            $product->markAsSold();

            // Auto-cancel semua penawaran pending/accepted pada produk ini
            $this->offerService->cancelAllOffersForProduct($product);

            // Jika kirim paket, buat Snap token Midtrans
            $snapToken = null;
            if ($metodePengiriman === 'kirim_paket') {
                $result = $this->paymentGateway->createTransaction($order);
                $snapToken = $result['token'];

                $order->update([
                    'midtrans_snap_token' => $snapToken,
                ]);

                // Buat record seller balance (ditahan)
                SellerBalance::create([
                    'user_id'  => $product->user_id,
                    'order_id' => $order->id,
                    'jumlah'   => $totalHarga,
                    'status'   => 'ditahan',
                ]);
            }

            // COD: status tetap pending (konfirmasi saat meetup)

            return [
                'order'      => $order->fresh(),
                'snap_token' => $snapToken,
            ];
        });
    }

    /**
     * Buyer konfirmasi terima barang.
     * Lepas dana escrow ke seller.
     */
    public function confirmReceipt(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update([
                'status'         => 'selesai',
                'dana_ditahan'   => false,
                'dana_dilepas_at' => now(),
            ]);

            // Update seller balance -> dilepas
            $balance = $order->sellerBalance;
            if ($balance && $balance->isDitahan()) {
                $balance->update([
                    'status'     => 'dilepas',
                    'dilepas_at' => now(),
                ]);
            }

            return $order->fresh();
        });
    }

    /**
     * Update status order dari Midtrans webhook.
     */
    public function handlePaymentSettlement(Order $order, string $transactionId, string $paymentType): Order
    {
        return DB::transaction(function () use ($order, $transactionId, $paymentType) {
            $order->update([
                'status'                  => 'dibayar',
                'midtrans_transaction_id' => $transactionId,
                'metode_pembayaran'       => $paymentType,
            ]);

            return $order->fresh();
        });
    }

    /**
     * Handle payment expiry/cancel dari Midtrans.
     */
    public function handlePaymentFailure(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order->update(['status' => 'dibatalkan']);

            // Kembalikan status produk ke active
            $order->product->update(['status' => 'active']);

            // Hapus seller balance yang ditahan
            $balance = $order->sellerBalance;
            if ($balance && $balance->isDitahan()) {
                $balance->delete();
            }

            return $order->fresh();
        });
    }

    /**
     * Seller menandai barang sudah dikirim.
     */
    public function markAsShipped(Order $order): Order
    {
        $order->update(['status' => 'dikirim']);
        return $order->fresh();
    }
}
