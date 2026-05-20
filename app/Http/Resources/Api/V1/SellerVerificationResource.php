<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerVerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'campusName'  => $this->nama_kampus,
            'nim'         => $this->nim,
            'ktmPhoto'    => asset('storage/' . $this->foto_ktm),
            'status'      => $this->status,
            'createdAt'   => $this->created_at->diffForHumans(),
            'user' => [
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
        ];
    }
}
