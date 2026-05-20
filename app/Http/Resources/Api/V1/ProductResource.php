<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            // Format harga menjadi integer murni
            'price'          => (int) $this->harga,
            'condition'      => $this->kondisi,
            'description'    => $this->deskripsi,
            'campusLocation' => $this->lokasi_kampus,
            'status'         => $this->status,
            'images'         => $this->foto_barang_urls,
            'seller'         => new UserResource($this->whenLoaded('user')),
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'createdAt'      => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
