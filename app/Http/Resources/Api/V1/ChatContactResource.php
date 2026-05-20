<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'avatar'      => $this->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=f3f4f6&color=43552c',
            'lastMessage' => $this->last_message,
            'createdAt'   => $this->created_at,
            'isRead'      => (bool) $this->is_read,
            'unreadCount' => $this->unread_count,
        ];
    }
}
