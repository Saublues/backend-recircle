<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'jumlah',
        'status',
        'catatan',
        'dilepas_at',
        'ditarik_at',
    ];

    protected $casts = [
        'jumlah'     => 'integer',
        'dilepas_at' => 'datetime',
        'ditarik_at' => 'datetime',
    ];

    // ── Relasi ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // ── Helpers ────────────────────────────────────

    public function isDitahan(): bool
    {
        return $this->status === 'ditahan';
    }

    public function isDilepas(): bool
    {
        return $this->status === 'dilepas';
    }
}
