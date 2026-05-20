<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'offeredPrice'     => $this->harga_tawar,
            'dealPrice'        => $this->harga_deal,
            'negotiatedPrice'  => $this->harga_deal,
            'negotiated_price' => $this->harga_deal,
            'message'          => $this->pesan,
            'status'           => $this->status,
            'expiredAt'        => $this->deal_expired_at ? $this->deal_expired_at->toISOString() : null,
            'isExpired'        => $this->isDealExpired(),
            'createdAt'        => $this->created_at->diffForHumans(),
            'product'       => [
                'id'         => $this->product->id ?? null,
                'name'       => $this->product->nama_barang ?? null,
                'price'      => $this->product->harga ?? null,
                'image'      => $this->product->foto_barang_url ?? null,
                'sellerName' => $this->product->user->name ?? null,
            ],
            'buyer' => $this->whenLoaded('user', function () {
                return [
                    'id'     => $this->user->id,
                    'name'   => $this->user->name,
                    'avatar' => $this->user->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->user->name) . '&background=43552c&color=fff',
                ];
            }),
        ];
    }
}
