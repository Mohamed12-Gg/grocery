<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteMealResource;
use App\Models\Meal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $favorites = $request->user()->favorites()
            ->with(['meal.category', 'meal.subcategory'])
            ->latest()
            ->get();

        $data = FavoriteMealResource::collection($favorites)->resolve();

        return $this->success(
            data: $data,
            message: 'Favorites retrieved successfully',
            extra: ['total_count' => $favorites->count()],
        );
    }

    public function toggle(Request $request, Meal $meal): JsonResponse
    {
        $user = $request->user();
        $favorite = $user->favorites()->forMeal($meal->id)->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
            $message = 'Removed from favorites';
        } else {
            $user->favorites()->create(['meal_id' => $meal->id]);
            $isFavorited = true;
            $message = 'Added to favorites';
        }

        return $this->success(
            data: ['meal_id' => $meal->id, 'is_favorited' => $isFavorited],
            message: $message,
        );
    }

    public function check(Request $request, Meal $meal): JsonResponse
    {
        $isFavorited = $request->user()->favorites()->forMeal($meal->id)->exists();

        return $this->success(
            data: ['meal_id' => $meal->id, 'is_favorited' => $isFavorited],
        );
    }

    public function remove(Request $request, Meal $meal): JsonResponse
    {
        $deleted = $request->user()->favorites()->forMeal($meal->id)->delete();

        if (!$deleted) {
            return $this->error('Meal was not in favorites', 404);
        }

        return $this->success(
            data: ['meal_id' => $meal->id, 'is_favorited' => false],
            message: 'Removed from favorites',
        );
    }
}