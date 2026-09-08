<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use illuminate\Http\JsonResponse;
use App\Traits\V1\ApiResponse;
use App\Http\Resources\Api\ReviewResource;
use App\Models\User;

class UserReviewsController extends Controller
{
    //
    use ApiResponse;
    public function index(Request $request,User $user): JsonResponse
    {
        $perPage = $request->query('per_page', 10);
        $reviews = $user->reviews()->with('meal')->approved()->latest()->paginate($perPage);

        return $this->successPaginated('User reviews retrieved successfully', ReviewResource::collection($reviews), $reviews);
    }
}
