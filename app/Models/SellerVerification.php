<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class SellerVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'foto_ktm',
        'nama_kampus',
        'nim',
        'status',
        'catatan_admin',
    ];

    protected $appends = ['foto_ktm_url'];

    protected function fotoKtmUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->foto_ktm) {
                    return filter_var($this->foto_ktm, FILTER_VALIDATE_URL) ? $this->foto_ktm : asset('storage/' . ltrim(str_replace('public/', '', $this->foto_ktm), '/'));
                }
                return asset('images/placeholder-ktm.png');
            }
        );
    }

    // ── Relasi ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ── Helpers ────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
