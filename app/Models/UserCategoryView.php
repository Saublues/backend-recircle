<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCategoryView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'jumlah_view',
    ];

    protected $casts = [
        'jumlah_view' => 'integer',
    ];

    // ── Relasi ─────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
