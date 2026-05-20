<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'senderId'   => $this->sender_id,
            'receiverId' => $this->receiver_id,
            'message'    => $this->message,
            'isRead'     => (bool) $this->is_read,
            'createdAt'  => $this->created_at->toISOString(),
            'time'       => $this->created_at->format('H:i'),
            'productId'  => $this->product_id,
            'product'    => $this->whenLoaded('product', function () {
                return [
                    'id'    => $this->product->id,
                    'name'  => $this->product->nama_barang,
                    'price' => $this->product->harga,
                    'image' => $this->product->foto_barang_url,
                ];
            }),
            'sender' => $this->relationLoaded('sender') && $this->sender ? [
                'id'   => $this->sender->id,
                'name' => $this->sender->name,
            ] : [
                'id'   => $this->sender_id,
                'name' => 'Unknown User',
            ],
        ];
    }
}
