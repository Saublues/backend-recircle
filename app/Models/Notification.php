<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = [
        'user_id', // ID user penerima (int, relasi ke SQL)
        'type',    // Tipe notifikasi (cth: 'order_update', 'new_offer', dll)
        'message', // Pesan notifikasi 
        'data',    // Data dinamis JSON / Array
        'is_read',
        'target_role', // 'seller' or 'buyer'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'is_read' => 'boolean',
        'data' => 'array',
        'target_role' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
