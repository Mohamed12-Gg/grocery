<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReviewRequest;
use App\Http\Requests\Api\UpdateReviewRequest;
use App\Http\Resources\Api\ReviewResource;
use App\Models\Review;
use App\Models\Meal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\V1\ApiResponse;
use App\Actions\Review\StoreAction;
use App\Actions\Review\GetMealAction;
use App\Filters\ReviewFilter;
use App\Actions\Review\GetUserAction;
class ReviewController extends Controller
{
    use ApiResponse;
    /**
     * Get all reviews (with filters)
     */
    public function index(Request $request, ReviewFilter $filter): JsonResponse
    {
        $reviews = $filter
            ->apply(
                Review::query()
                    ->with(['user', 'meal'])
                    ->latest(),
                $request,
            )
            ->paginate($request->input('per_page', 15));

        return $this->success('Reviews retrieved successfully', ReviewResource::collection($reviews), 200);
    }

    /**
     * Store a new review
     */
    public function store(StoreReviewRequest $request, StoreAction $storeAction): JsonResponse
    {
        $review = $storeAction->execute($request->user(), $request->validated());
        return $this->success('Review submitted successfully. Waiting for admin approval.', new ReviewResource($review->load(['user', 'meal'])), 201);
    }

    /**
     * Get single review
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['user', 'meal']);
        return $this->success('Review retrieved successfully', new ReviewResource($review));
    }

    /**
     * Update review (only by owner or admin)
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        // Check authorization (owner or admin)
        $this->authorize('update', $review);

        $review->update($request->validated());

        return $this->success('Review updated successfully', new ReviewResource($review->load(['user', 'meal'])));
    }

    /**
     * Delete review (only by owner or admin)
     */
    public function destroy(Review $review): JsonResponse
    {
        // Check authorization (owner or admin)
        $this->authorize('delete', $review);

        $review->delete();

        return $this->success('Review deleted successfully');
    }

    /**
     * Get reviews for a specific meal
     */
    public function getMealReviews(Meal $meal, Request $request, GetMealAction $action): JsonResponse
    {
        $data = $action->execute($meal, $request->integer('per_page', 10));

        return $this->successPaginated('Reviews for meal retrieved successfully', ReviewResource::collection($data['reviews']), $data['reviews'], [
            'meal' => $data['meal'],
        ]);
    }

    /**
     * Get user's reviews
     */
    public function getUserReviews(Request $request, GetUserAction $action): JsonResponse
    {
        $reviews = $action->execute($request->user(), $request->integer('per_page', 10));

        return $this->successPaginated('User reviews retrieved successfully', ReviewResource::collection($reviews), $reviews);
    }

    /**
     * Get review statistics for a meal
     */
    public function getMealReviewStats(Meal $meal): JsonResponse
    {
        return $this->success('Meal review statistics retrieved successfully', Review::ratingStatsFor($meal->id));
    }
}
