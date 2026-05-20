<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orderCode' => $this->kode_pesanan,
            'totalPrice' => (int) $this->total_harga,
            'shippingMethod' => $this->metode_pengiriman,
            'status' => $this->status,
            'paymentMethod' => $this->metode_pembayaran,
            'shippingAddress' => $this->alamat_pengiriman,
            'notes' => $this->catatan,
            'courierInfo' => $this->courier_info,
            'isPending' => $this->status === 'pending',
            'snapToken' => $this->when($this->status === 'pending', $this->midtrans_snap_token),
            'midtransClientKey' => config('services.midtrans.client_key') ?? config('midtrans.client_key'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'product' => new ProductResource($this->whenLoaded('product')),
            
            // Added properties for Transactions and other views
            'buyer' => $this->user?->name,
            'seller' => $this->product?->user?->name,
            'payment_status' => $this->status === 'pending' ? 'unpaid' : ($this->status === 'dibatalkan' ? 'failed' : 'paid'),
            'paymentStatus' => $this->status === 'pending' ? 'unpaid' : ($this->status === 'dibatalkan' ? 'failed' : 'paid'),

            // Snake_case keys for direct fallback compatibility
            'kode_pesanan' => $this->kode_pesanan,
            'total_harga' => (int) $this->total_harga,
            'metode_pengiriman' => $this->metode_pengiriman,
            'metode_pembayaran' => $this->metode_pembayaran,
            'alamat_pengiriman' => $this->alamat_pengiriman,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}