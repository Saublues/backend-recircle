<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OfferService
{
    /**
     * Buat penawaran baru dari buyer.
     */
    public function createOffer(User $user, Product $product, int $hargaTawar, ?string $pesan = null): Offer
    {
        // Tidak bisa menawar produk sendiri
        if ($product->user_id === $user->id) {
            throw new \InvalidArgumentException('Tidak bisa menawar produk sendiri.');
        }

        // Tidak bisa menawar produk yang sudah terjual
        if (!$product->isActive()) {
            throw new \InvalidArgumentException('Produk sudah tidak tersedia.');
        }

        // Harga tawar harus lebih kecil dari harga asli
        if ($hargaTawar >= $product->harga) {
            throw new \InvalidArgumentException('Harga tawaran harus lebih rendah dari harga asli.');
        }

        return Offer::create([
            'user_id'     => $user->id,
            'product_id'  => $product->id,
            'harga_tawar' => $hargaTawar,
            'pesan'       => $pesan,
            'status'      => 'pending',
        ]);
    }

    /**
     * Seller menerima penawaran:
     * - Set harga_deal = harga_tawar
     * - Set deal_expired_at = 24 jam dari sekarang
     * - Auto-reject semua penawaran pending lainnya pada produk yang sama
     */
    public function acceptOffer(Offer $offer): Offer
    {
        return DB::transaction(function () use ($offer) {
            $offer->update([
                'status'          => 'accepted',
                'harga_deal'      => $offer->harga_tawar,
                'deal_expired_at' => now()->addHours(24),
            ]);

            // Auto-reject semua pending offers lainnya pada produk ini
            Offer::where('product_id', $offer->product_id)
                ->where('id', '!=', $offer->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected']);

            return $offer->fresh();
        });
    }

    /**
     * Seller menolak penawaran.
     */
    public function rejectOffer(Offer $offer): Offer
    {
        $offer->update(['status' => 'rejected']);
        return $offer->fresh();
    }

    /**
     * Batalkan semua penawaran pending & accepted pada produk tertentu.
     * Dipanggil oleh OrderService saat produk dibeli full price.
     */
    public function cancelAllOffersForProduct(Product $product): int
    {
        return Offer::where('product_id', $product->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->update(['status' => 'cancelled']);
    }

    /**
     * Ambil penawaran aktif (accepted, belum expired) untuk buyer tertentu.
     */
    public function getActiveDeals(User $user)
    {
        return Offer::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->where('deal_expired_at', '>', now())
            ->with('product.images', 'product.user')
            ->latest()
            ->get();
    }
}
