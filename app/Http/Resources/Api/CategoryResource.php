<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'description' => $this->description,
                    'image_url' => $this->image_url,
                    'sort_order' => $this->sort_order,
                    'meals' => MealResource::collection($this->meals),
                    'created_at' => $this->created_at,
                    'updated_at' => $this->updated_at,
                ];
    
    }
}
