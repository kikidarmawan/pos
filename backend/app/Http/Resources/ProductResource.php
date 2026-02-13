<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'code' => $this->code,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'base_unit_id' => $this->base_unit_id,
            'base_price' => $this->base_price,
            'minimum_stock' => $this->minimum_stock,
            'is_active' => $this->is_active,
            'total_stock' => $this->total_stock ?? 0,
            'image' => $this->image ? asset('storage/' . $this->image) : null,

            // Relationships in snake_case for frontend consistency
            'category' => $this->whenLoaded('category'),
            'base_unit' => $this->whenLoaded('baseUnit'),
            'product_units' => $this->whenLoaded('productUnits'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
