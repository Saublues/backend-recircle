<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type'        => $this['type'],
            'title'       => $this['title'],
            'description' => $this['description'],
            'time'        => $this['time'],
            'timestamp'   => $this['timestamp'],
        ];
    }
}
