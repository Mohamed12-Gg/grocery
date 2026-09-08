<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\V1\ApiResponse;
use App\Http\Resources\Api\ProfileResource;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\SessionResource;
use App\Actions\Profile\UpdateInfoAction;
use App\Actions\Profile\RevokeSessionAction;
use App\Http\Resources\Api\UpdateProfileResource;
class ProfileController extends Controller
{
    use ApiResponse;
    public function show(Request $request): JsonResponse
    {
       
        $user = $request->user();
        $user->load(['addresses', 'favorites.meal.category', 'favorites.meal.subcategory']);
        $addresses = $user->addresses()->orderByDesc('is_default')->latest()->get();
        $orders = $user
            ->orders()
            ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
            ->latest()
            ->get();
        $notifications = $user
            ->notifications()
            ->whereIn('data->type', ['order_confirmation', 'order_shipped', 'delivery_updates'])
            ->latest()
            ->take(20)
            ->get();
        $sessions = $user->tokens()->get();

        return $this->success('Profile retrieved successfully', new ProfileResource([
            'user' => $user,
            'addresses' => $addresses,
            'orders' => $orders,
            'notifications' => $notifications,
            'sessions' => $sessions,
        ]));
    }

    /**
     * Update profile information
     */
    public function updateInfo(UpdateProfileRequest $request, UpdateInfoAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->validated());

        return $this->success('Profile information updated successfully', new UpdateProfileResource($user));
    }

    /**
     * List active sessions/devices (Sanctum tokens). User can logout from each.
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();

        $sessions = $user->tokens()->get();

        return $this->success('Sessions retrieved successfully', SessionResource::collection($sessions));
    }

    /**
     * Revoke a session/device (logout from that token).
     */
    public function destroySession(Request $request, string $tokenId, RevokeSessionAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $tokenId);
        if ($result === 'current') {
            return $this->error('Cannot revoke the current session', 400);
        } elseif ($result === false) {
            return $this->error('Session not found', 404);
        }
        return $this->success('Session revoked successfully');
    }
}
