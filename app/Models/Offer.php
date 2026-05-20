<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'harga_tawar',
        'pesan',
        'status',
        'harga_deal',
        'deal_expired_at',
    ];

    protected $casts = [
        'harga_tawar' => 'integer',
        'harga_deal'  => 'integer',
        'deal_expired_at' => 'datetime',
    ];

    // ── Relasi ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    // ── Scopes ─────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // ── Helpers ────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDealExpired(): bool
    {
        return $this->isAccepted()
            && $this->deal_expired_at !== null
            && $this->deal_expired_at->isPast();
    }
}