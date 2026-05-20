<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->nama_barang,
            'seller'    => $this->user->name ?? 'Unknown',
            'category'  => $this->category->nama_kategori ?? 'Umum',
            'price'     => $this->harga,
            'status'    => $this->status ?? 'pending',
            'image'     => $this->foto_barang_url,
            'createdAt' => $this->created_at->format('d M Y'),
        ];
    }
}
