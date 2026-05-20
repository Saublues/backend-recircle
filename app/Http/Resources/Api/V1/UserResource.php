<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'is_seller'  => (bool) $this->is_seller,
            'kampus'     => $this->kampus,
            'bio'        => $this->bio,
            'nomor_wa'   => $this->nomor_wa,
            'avatar_url' => $this->avatar_url,
            'wishlist_ids'=> $this->wishlists()->pluck('product_id')->toArray(),
            'komerceDestinationId' => $this->komerce_destination_id,
            'komerceDestinationLabel' => $this->komerce_destination_label,
            'joinedAt'   => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
