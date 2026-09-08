<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileImageRequest;
use App\Actions\Profile\UpdateImageAction;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Api\UpdateProfileResource;
use Illuminate\Http\Request;
use App\Actions\Profile\DeleteImageAction;

class ProfileImageController extends Controller
{
    use ApiResponse;
    //
    public function update(UpdateProfileImageRequest $request, UpdateImageAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->file('image'));

        return $this->success('Profile image updated successfully', new UpdateProfileResource($user));
    }

    public function delete(Request $request, DeleteImageAction $action): JsonResponse
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
}
