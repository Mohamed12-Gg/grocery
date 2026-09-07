<?php
namespace App\Actions\Review;

class GetUserAction
{
    public function execute($user, int $perPage = 10)
    {
        $reviews = $user
            ->reviews()
            ->with('meal')
            ->approved()
            ->latest()
            ->paginate($perPage);

        return $reviews;
    }
}