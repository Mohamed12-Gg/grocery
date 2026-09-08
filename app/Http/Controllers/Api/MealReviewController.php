<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meal;
use App\Http\Resources\Api\ReviewResource;
use Illuminate\Http\JsonResponse;
use App\Models\Review;
use App\Traits\V1\ApiResponse;

class MealReviewController extends Controller
{
    //
    use ApiResponse;
    public function index(Meal $meal, Request $request) :JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $reviews = $meal->reviews()->with('user')->approved()->latest()->paginate($perPage);

        $mealData = [
            'id' => $meal->id,
            'name' => $meal->name,
            'average_rating' => round(Review::getAverageRating($meal->id), 1),
            'total_reviews' => Review::getTotalReviews($meal->id),
        ];

        return $this->successPaginated('Meal reviews retrieved successfully', ReviewResource::collection($reviews), $mealData);
    }
    public function stats(Meal $meal): JsonResponse
    {
         return $this->success('Meal review statistics retrieved successfully', Review::ratingStatsFor($meal->id));
    }
}
