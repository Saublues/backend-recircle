<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this refers to the seller array we format in the controller
        return [
            'seller' => [
                'id'          => $this['seller']['id'],
                'name'        => $this['seller']['name'],
                'universitas' => $this['seller']['universitas'],
            ],
            'items' => CartItemResource::collection($this['items']),
        ];
    }
}
