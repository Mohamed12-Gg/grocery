<?php

namespace App\Actions\Review;

use App\Models\Review;
use App\Models\User;
class StoreAction
{
    public function execute(User $user, array $data): Review
    {
        if (Review::hasUserReviewed($user->id, $data['meal_id'])) {
            throw new \DomainException('You have already submitted a review for this meal.');
        }

        return Review::create([
            'user_id' => $user->id,
            'meal_id' => $data['meal_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'images' => $data['images'] ?? null,
            'is_approved' => false,
        ]);
    }
}
