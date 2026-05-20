<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public function __construct(array $attributes = [])
    {
        $this->connection = config('database.default');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'is_seller',
        'avatar',
        'nomor_wa',
        'kampus',
        'bio',
        'komerce_destination_id',
        'komerce_destination_label',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'is_seller' => 'boolean',
        'notifications_read_at' => 'datetime',
    ];

    protected $appends = ['avatar_url'];

    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->avatar) {
                    return filter_var($this->avatar, FILTER_VALIDATE_URL) ? $this->avatar : asset('storage/' . ltrim(str_replace('public/', '', $this->avatar), '/'));
                }
                return asset('images/default-avatar.png');
            }
        );
    }

    // ── Relasi ─────────────────────────────────────

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function sellerVerifications()
    {
        return $this->hasMany(SellerVerification::class);
    }

    public function sellerBalances()
    {
        return $this->hasMany(SellerBalance::class);
    }

    public function categoryViews()
    {
        return $this->hasMany(UserCategoryView::class);
    }

    public function wishlists()
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    // ── Helper Methods ─────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSeller(): bool
    {
        return $this->is_seller;
    }

    public function latestVerification()
    {
        return $this->hasOne(SellerVerification::class)->latestOfMany();
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('c'); // ISO 8601
    }
}