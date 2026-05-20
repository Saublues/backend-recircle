<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ActivityLog;

class MongoLogService
{
    /**
     * Trigger a new notification
     * 
     * @param int $userId
     * @param string $type
     * @param string $message
     * @param array $data
     * @return Notification
     */
    public static function notify(int $userId, string $type, string $message, array $data = [], string $targetRole = 'buyer')
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
            'target_role' => $targetRole,
        ]);
    }

    /**
     * Log user activity
     * 
     * @param int|null $userId
     * @param string $event
     * @param array $metaData
     * @return ActivityLog
     */
    public static function log(?int $userId, string $event, array $metaData = [])
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'event' => $event,
            'url_path' => request()->path(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'meta_data' => $metaData,
        ]);
    }
}
