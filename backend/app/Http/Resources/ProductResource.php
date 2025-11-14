<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => number_format($this->price, 2),
            'price_raw' => $this->price,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'stock' => $this->stock,
            'category' => $this->category,
            'seller' => [
                'id' => $this->seller?->id,
                'name' => $this->seller?->name,
                'email' => $this->seller?->email,
            ],
            'featured' => $this->featured ?? false,
            'views' => $this->views ?? 0,
            'status' => $this->status ?? 'active',
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
