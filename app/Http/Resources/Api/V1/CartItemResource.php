<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'productId'       => $this->product_id,
            'offerId'         => $this->offer_id,
            'productName'     => $this->product->nama_barang,
            'productImage'    => $this->product->foto_barang_url,
            'normalPrice'     => (int) $this->product->harga,
            'negotiatedPrice' => $this->offer ? (int) $this->offer->harga_deal : null,
            'expiresAt'       => $this->offer ? $this->offer->deal_expired_at : null,
            'addedAt'         => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
