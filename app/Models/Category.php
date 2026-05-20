<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_kategori',
        'slug',
        'ikon',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function userViews()
    {
        return $this->hasMany(UserCategoryView::class);
    }
}