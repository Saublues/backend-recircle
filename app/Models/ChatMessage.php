<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ChatMessage extends Model
{
    // Gunakan koneksi MongoDB sesuai database.php
    protected $connection = 'mongodb';
    
    // Nama collection di MongoDB
    protected $collection = 'chat_messages';

    protected $fillable = [
        'room_id', // ID gabungan untuk indexing: min(sender, receiver)_max(sender, receiver)
        'sender_id', // Int: referensi ID users SQL
        'receiver_id', // Int: referensi ID users SQL
        'product_id', // Int: (nullable) referensi ID products SQL
        'message',
        'is_read',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        // Pastikan ID foreign key terbaca sebagai integer supaya relasi cocok dengan SQL
        'sender_id' => 'integer',
        'receiver_id' => 'integer',
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Helper membuat room_id secara otomatis
     */
    public static function generateRoomId(int $user1, int $user2): string
    {
        return min($user1, $user2) . '_' . max($user1, $user2);
    }

    /**
     * Cross-Database Relations
     * Laravel-MongoDB memungkinkan relasi BelongsTo ke model SQL.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
