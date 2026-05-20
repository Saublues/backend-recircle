<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'amount'      => $this->jumlah,
            'status'      => $this->status,
            'releasedAt'  => $this->dilepas_at?->diffForHumans(),
            'createdAt'   => $this->created_at->diffForHumans(),
            'orderCode'   => $this->order->kode_pesanan ?? null,
            'productName' => $this->order->product->nama_barang ?? 'Produk Dihapus',
        ];
    }
}
