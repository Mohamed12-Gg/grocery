<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\V1\ApiResponse;
use App\Http\Resources\Api\ProfileResource;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\UpdateProfileImageRequest;
use App\Http\Resources\Api\SessionResource;
use App\Actions\Profile\UpdateInfoAction;
use App\Actions\Profile\UpdateImageAction;
use App\Actions\Profile\DeleteImageAction;
use App\Actions\Profile\RevokeSessionAction;
use App\Http\Resources\Api\UpdateProfileResource;
use App\Actions\Profile\GetProfileAction;
class ProfileController extends Controller
{
    use ApiResponse;
    public function show(Request $request, GetProfileAction $action): JsonResponse
    {
        $data = $action->execute($request->user());
        return $this->success('Profile retrieved successfully', new ProfileResource($data));
    }

    /**
     * Update profile image
     */
    public function updateImage(UpdateProfileImageRequest $request, UpdateImageAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->file('image'));

        return $this->success('Profile image updated successfully', new UpdateProfileResource($user));
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
     * Delete profile image
     */
    public function deleteImage(Request $request, DeleteImageAction $action): JsonResponse
    {
        $user = $request->user();

        $deleted = $action->execute($user);
        if (!$deleted) {
            return $this->error('No profile image to delete', 400);
        }

        return $this->success('Profile image deleted successfully', [
            'profile_image' => null,
            'profile_image_url' => null,
        ]);
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
