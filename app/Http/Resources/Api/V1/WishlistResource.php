<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->nama_barang,
            'price'          => (int) $this->harga,
            'condition'      => $this->kondisi,
            'campusLocation' => $this->lokasi_kampus,
            'images'         => $this->foto_barang_urls,
            'status'         => $this->status,
            'seller'         => new UserResource($this->whenLoaded('user')),
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'wishlistedAt'   => $this->pivot ? $this->pivot->created_at?->format('Y-m-d H:i:s') : null,
        ];
    }
}
