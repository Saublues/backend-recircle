<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_pesanan',
        'user_id',
        'product_id',
        'offer_id',
        'total_harga',
        'metode_pengiriman',
        'status',
        'alamat_pengiriman',
        'catatan',
        'midtrans_transaction_id',
        'midtrans_snap_token',
        'metode_pembayaran',
        'dana_ditahan',
        'dana_dilepas_at',
    ];

    protected $casts = [
        'total_harga'    => 'integer',
        'dana_ditahan'   => 'boolean',
        'dana_dilepas_at' => 'datetime',
    ];

    // ── Boot ───────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->kode_pesanan)) {
                $order->kode_pesanan = 'RC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    // ── Relasi ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function sellerBalance()
    {
        return $this->hasOne(SellerBalance::class);
    }

    // ── Helpers ────────────────────────────────────

    public function isCod(): bool
    {
        return $this->metode_pengiriman === 'cod';
    }

    public function isKirimPaket(): bool
    {
        return $this->metode_pengiriman === 'kirim_paket';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDibayar(): bool
    {
        return $this->status === 'dibayar';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }
}
