<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product'       => $this->product->nama_barang ?? 'Produk Dihapus',
            'buyer'         => $this->user->name ?? 'Unknown',
            'seller'        => $this->product->user->name ?? 'Unknown',
            'originalPrice' => $this->product->harga ?? 0,
            'offeredPrice'  => $this->harga_tawar,
            'status'        => $this->status,
            'createdAt'     => $this->created_at->format('d M Y'),
        ];
    }
}
