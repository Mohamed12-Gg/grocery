<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateNotificationCategoryRequest;
use Auth;

class NotificationCategoryController extends Controller
{
    public function update(UpdateNotificationCategoryRequest $request, string $category): JsonResponse
    {
        $validated = $request->validated();

        $user = Auth::user();
        $settings = $user->initializeNotificationSettings();

        $fields = config("category_fields.{$category}", []);

        if (empty($fields)) {
            return $this->error('Invalid category', 400);
        }

        $updateData = [];
        foreach ($fields as $field) {
            $updateData[$field] = (bool) $validated['enabled'];
        }

        $settings->update($updateData);

        return $this->success('Notification settings updated successfully', $this->formatSettings($settings->fresh()));
    }
}
