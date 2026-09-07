<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $meal = $this->meal;

        return [
            'meal_id' => $meal?->id,
            'title' => $meal?->title,
            'image_url' => $meal?->image_url,
            'category' => $meal?->category ? [
                'id' => $meal->category->id,
                'name' => $meal->category->name,
            ] : null,
            'total_quantity_purchased' => (int) $this->total_quantity,
            'total_spent' => (float) $this->total_spent,
        ];
    }
}