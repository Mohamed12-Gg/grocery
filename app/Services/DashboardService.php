<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Http\Resources\MealPurchaseResource;
use App\Http\Resources\OrderSummaryResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData(User $user): array
    {
        return [
            'overview' => $this->getOverview($user),
            'shopping_insights' => $this->getShoppingInsights($user),
            'category_distribution' => $this->getCategoryDistribution($user),
            'recent_orders' => $this->getRecentOrders($user),
            'top_purchases' => $this->getTopPurchases($user),
        ];
    }

    private function getOverview(User $user): array
    {
        $activeOrder = Order::forUser($user->id)
            ->active()
            ->with(['items.meal', 'address'])
            ->latest()
            ->first();

        $cart = $user->activeCart()->with('items')->first();
        if ($cart) {
            $cart->calculateTotals();
        }

        $upcomingDelivery = Order::forUser($user->id)
            ->whereIn('status', OrderStatus::activeStatuses())
            ->whereNotNull('estimated_delivery_time')
            ->orderBy('estimated_delivery_time')
            ->first();

        return [
            'tracking_order' => $activeOrder ? [
                'id' => $activeOrder->id,
                'order_number' => $activeOrder->order_number,
                'status' => $activeOrder->status,
                'status_description' => $activeOrder->status_description,
                'status_position' => $activeOrder->status_position,
            ] : null,
            'loyalty_points' => (int) ($user->loyalty_points ?? 0),
            'store_credits' => (float) ($user->store_credits ?? 0),
            'current_cart' => [
                'items_count' => $cart?->items->sum('quantity') ?? 0,
                'total' => (float) ($cart->total ?? 0),
                'last_updated' => $cart?->updated_at,
            ],
            'upcoming_delivery' => $upcomingDelivery ? [
                'order_id' => $upcomingDelivery->id,
                'order_number' => $upcomingDelivery->order_number,
                'date' => $upcomingDelivery->estimated_delivery_time?->format('Y-m-d'),
                'time' => $upcomingDelivery->estimated_delivery_time?->format('H:i'),
                'estimated_delivery_time' => $upcomingDelivery->estimated_delivery_time,
            ] : null,
        ];
    }

    private function getShoppingInsights(User $user): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $ordersThisMonth = Order::forUser($user->id)
            ->notCancelled()
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get(['id', 'total', 'created_at', 'discount']);

        $ordersCount = $ordersThisMonth->count();
        $monthlySpend = $ordersThisMonth->sum('total');

        $averageDaysBetweenOrders = $this->averageDaysBetween($ordersThisMonth->pluck('created_at'));

        $discountSavings = Order::forUser($user->id)->notCancelled()->sum('discount');

        $mealSavings = OrderItem::whereHas('order', fn ($q) => $q->forUser($user->id)->notCancelled())
            ->join('meals', 'meals.id', '=', 'order_items.meal_id')
            ->whereNotNull('meals.discount_price')
            ->sum(DB::raw('(meals.price - meals.discount_price) * order_items.quantity'));

        return [
            'monthly_spend' => (float) $monthlySpend,
            'orders_this_month' => [
                'count' => $ordersCount,
                'average_days_between_orders' => $averageDaysBetweenOrders,
            ],
            'total_savings' => (float) ($discountSavings + $mealSavings),
            'average_order_value' => $ordersCount > 0 ? round($monthlySpend / $ordersCount, 2) : 0,
        ];
    }

    private function averageDaysBetween(Collection $dates): float
    {
        if ($dates->count() <= 1) {
            return 0;
        }

        $sorted = $dates->sort()->values();
        $totalDays = 0;

        for ($i = 1; $i < $sorted->count(); $i++) {
            $totalDays += $sorted[$i]->diffInDays($sorted[$i - 1]);
        }

        return round($totalDays / ($sorted->count() - 1), 1);
    }

    private function getCategoryDistribution(User $user): array
    {
        $rows = OrderItem::whereHas('order', fn ($q) => $q->forUser($user->id)->notCancelled())
            ->join('meals', 'meals.id', '=', 'order_items.meal_id')
            ->join('categories', 'categories.id', '=', 'meals.category_id')
            ->select('categories.id as category_id', 'categories.name as category_name')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_quantity')
            ->get();

        $totalItems = $rows->sum('total_quantity');

        return $rows->map(fn ($row) => [
            'category_id' => $row->category_id,
            'category_name' => $row->category_name,
            'total_quantity' => (int) $row->total_quantity,
            'percentage' => $totalItems > 0 ? round(($row->total_quantity / $totalItems) * 100, 1) : 0,
        ])->toArray();
    }

    private function getRecentOrders(User $user, int $limit = 5): array
    {
        $orders = Order::forUser($user->id)
            ->with(['items.meal.category', 'items.meal.subcategory', 'address'])
            ->latest()
            ->limit($limit)
            ->get();

        return OrderSummaryResource::collection($orders)->resolve();
    }

    private function getTopPurchases(User $user, int $limit = 10): array
    {
        $topMeals = OrderItem::whereHas('order', fn ($q) => $q->forUser($user->id)->notCancelled())
            ->with('meal.category', 'meal.subcategory')
            ->select('meal_id')
            ->selectRaw('SUM(quantity) as total_quantity, SUM(subtotal) as total_spent')
            ->groupBy('meal_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return MealPurchaseResource::collection($topMeals)->resolve();
    }
}