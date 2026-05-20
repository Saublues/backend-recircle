<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public function __construct(array $attributes = [])
    {
        $this->connection = config('database.default');
        parent::__construct($attributes);
    }

    protected $fillable = [
        'user_id',
        'category_id',
        'nama_barang',
        'harga',
        'kondisi',
        'deskripsi',
        'lokasi_kampus',
        'image',
        'status',
        'is_archived',
    ];

    protected $casts = [
        'harga' => 'integer',
    ];

    protected $appends = ['foto_barang', 'foto_barang_url', 'foto_barang_urls'];

    protected function fotoBarang(): Attribute
    {
        return Attribute::make(
            get: function () {
                $urls = [];

                if ($this->image) {
                    $decoded = json_decode($this->image, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $path) {
                            $urls[] = filter_var($path, FILTER_VALIDATE_URL) ? $path : asset('storage/' . ltrim(str_replace('public/', '', $path), '/'));
                        }
                    } else {
                        $urls[] = filter_var($this->image, FILTER_VALIDATE_URL) ? $this->image : asset('storage/' . ltrim(str_replace('public/', '', $this->image), '/'));
                    }
                }

                if ($this->relationLoaded('images')) {
                    foreach ($this->images as $img) {
                        $urls[] = filter_var($img->path, FILTER_VALIDATE_URL) ? $img->path : asset('storage/' . ltrim(str_replace('public/', '', $img->path), '/'));
                    }
                }

                if (empty($urls)) {
                    $urls[] = asset('images/placeholder-product.png');
                }

                return $urls;
            }
        );
    }

    public function getFotoBarangUrlAttribute()
    {
        $fallback = asset('images/placeholder-product.png');

        if (empty($this->image)) {
            return $fallback;
        }

        $decoded = json_decode($this->image, true);
        $path = null;

        if (is_array($decoded) && count($decoded) > 0) {
            $path = $decoded[0];
        } elseif (is_string($this->image)) {
            $path = $this->image;
        }

        if ($path) {
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            // Bersihkan path jika ada prefix storage atau public
            $cleanPath = ltrim(str_replace(['public/', '/storage/', 'storage/'], ['', '', ''], $path), '/');
            return Storage::url($cleanPath);
        }

        return $fallback;
    }

    public function getFotoBarangUrlsAttribute()
    {
        $fallback = asset('images/placeholder-product.png');
        $urls = [];

        if (!empty($this->image)) {
            $decoded = json_decode($this->image, true);

            if (is_array($decoded) && count($decoded) > 0) {
                foreach ($decoded as $path) {
                    if (filter_var($path, FILTER_VALIDATE_URL)) {
                        $urls[] = $path;
                    } else {
                        $cleanPath = ltrim(str_replace(['public/', '/storage/', 'storage/'], ['', '', ''], $path), '/');
                        $urls[] = Storage::url($cleanPath);
                    }
                }
            } elseif (is_string($this->image)) {
                if (filter_var($this->image, FILTER_VALIDATE_URL)) {
                    $urls[] = $this->image;
                } else {
                    $cleanPath = ltrim(str_replace(['public/', '/storage/', 'storage/'], ['', '', ''], $this->image), '/');
                    $urls[] = Storage::url($cleanPath);
                }
            }
        }

        if (empty($urls)) {
            return [$fallback];
        }

        return $urls;
    }

    // ── Relasi ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    // ── Scopes ─────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    // ── Helpers ────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markAsSold(): void
    {
        $this->update(['status' => 'sold']);
    }
}