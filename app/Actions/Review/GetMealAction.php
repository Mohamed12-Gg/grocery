<?php

namespace App\Actions\Review;
use App\Models\Meal;
use App\Models\Review;
class GetMealAction
{
    public function execute(Meal $meal, int $perPage = 10)
    {  
        $reviews = $meal
            ->reviews()
            ->with('user')
            ->approved()
            ->latest()
            ->paginate($perPage);

        $mealData = [
            'id' => $meal->id,
            'name' => $meal->name,
            'average_rating' => round(
                Review::getAverageRating($meal->id),
                1
            ),
            'total_reviews' => Review::getTotalReviews($meal->id),
        ];

        return [
            'reviews' => $reviews,
            'meal' => $mealData,
        ];
    }
}