<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'totalProducts'  => $this->resource['total_products'] ?? 0,
                'activeProducts' => $this->resource['active_products'] ?? 0,
                'soldProducts'   => $this->resource['sold_products'] ?? 0,
                'activeOrders'   => $this->resource['active_orders'] ?? 0,
            ],
            'wallet' => [
                'active' => 0,
                'escrow' => 0,
            ],
        ];
    }
}
