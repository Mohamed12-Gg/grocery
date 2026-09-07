<?php 
namespace App\Actions\Profile;
use App\Models\User;

class GetProfileAction
{
    public function execute(User $user): array
    {
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
        return ['user' => $user, 'addresses' => $addresses, 'orders' => $orders, 'notifications' => $notifications, 'sessions' => $sessions];
    }
}
