<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateNotificationSettingsRequest;
use App\Http\Resources\Api\NotificationSettingResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Notification;

class NotificationSettingController extends Controller
{
    use ApiResponse;

    /**
     * Get user notification settings
     */
    public function index()
    {
        $user = Auth::user();
        $settings = $user->initializeNotificationSettings();

        return $this->success('Notification settings retrieved successfully', NotificationSettingResource::collection($settings));
    }

    /**
     * Update notification settings.
     * Only accepts true, false, 0, or 1 for each setting; invalid values (e.g. 4) return 422.
     */
    public function update(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $settings = $user->initializeNotificationSettings();
        $settings->update($validated);

        return $this->success('Notification settings updated successfully', NotificationSettingResource::make($settings->fresh()));
    }
}
