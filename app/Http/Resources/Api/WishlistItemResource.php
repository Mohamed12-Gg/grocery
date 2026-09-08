<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WishlistItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $meal = $this->meal;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'image_url' => $this->image_url,
            ...$this->getApiPriceAttributes(),
            'has_offer' => $this->hasOffer(),
            'category' => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name] : null,
            'is_favorited' => true,
            'favorited_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
