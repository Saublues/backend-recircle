<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ActivityLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'activity_logs';

    protected $fillable = [
        'user_id', 
        'event',      // Cth: 'login', 'view_product', 'checkout'
        'url_path',
        'ip_address',
        'user_agent',
        'meta_data',  // Info dinamis (JSON/Array)
    ];

    protected $casts = [
        'user_id' => 'integer',
        'meta_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
