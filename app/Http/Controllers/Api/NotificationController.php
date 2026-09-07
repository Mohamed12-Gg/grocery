<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Order;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get all notifications for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = $this->buildNotificationsQuery($request)->paginate(Controller::PAGINATION_SIZE);
        $transformed = $notifications->getCollection()->map(fn ($n) => $this->transformNotification($n))->values();
        $notifications->setCollection($transformed);

        return $this->success([
            'notifications' => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Same as index but attaches related models (meal, order) when referenced in notification data.
     */
    public function indexWithResources(Request $request): JsonResponse
    {
        $user = Auth::user();

        $notifications = $this->buildNotificationsQuery($request)->paginate(Controller::PAGINATION_SIZE);
        $pageItems = $notifications->getCollection();

        $mealIds = [];
        $orderIds = [];
        foreach ($pageItems as $n) {
            $d = $this->notificationDataAsArray($n->data);
            if (! empty($d['meal_id']) && is_numeric($d['meal_id'])) {
                $mealIds[] = (int) $d['meal_id'];
            }
            if (! empty($d['order_id']) && is_numeric($d['order_id'])) {
                $orderIds[] = (int) $d['order_id'];
            }
        }
        $mealIds = array_values(array_unique($mealIds));
        $orderIds = array_values(array_unique($orderIds));

        $meals = $mealIds === []
            ? collect()
            : Meal::query()->with('category')->whereIn('id', $mealIds)->get()->keyBy('id');
        $orders = $orderIds === []
            ? collect()
            : Order::query()->whereIn('id', $orderIds)->get()->keyBy('id');

        $transformed = $pageItems->map(function (DatabaseNotification $notification) use ($meals, $orders) {
            $row = $this->transformNotification($notification);
            $d = $this->notificationDataAsArray($notification->data);
            $resources = [];

            if (! empty($d['meal_id']) && is_numeric($d['meal_id'])) {
                $meal = $meals->get((int) $d['meal_id']);
                if ($meal) {
                    $resources['meal'] = [
                        'id' => $meal->id,
                        'title' => $meal->title,
                        'slug' => $meal->slug,
                        'image_url' => $meal->image_url,
                        ...$meal->getApiPriceAttributes(),
                        'has_offer' => $meal->hasOffer(),
                        'category' => $meal->category ? [
                            'id' => $meal->category->id,
                            'name' => $meal->category->name,
                        ] : null,
                    ];
                }
            }

            if (! empty($d['order_id']) && is_numeric($d['order_id'])) {
                $order = $orders->get((int) $d['order_id']);
                if ($order) {
                    $resources['order'] = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'status' => $order->status,
                        'total' => (string) $order->total,
                        'placed_at' => $order->placed_at?->toIso8601String(),
                        'created_at' => $order->created_at?->toIso8601String(),
                    ];
                }
            }

            $row['resources'] = $resources;

            return $row;
        })->values();

        $notifications->setCollection($transformed);

        return $this->success([
            'notifications' => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /** Apply list filters to the authenticated user's notifications query. */
    private function buildNotificationsQuery(Request $request)
    {
        $user = Auth::user();
        $query = $user->notifications();

        if ($request->has('read')) {
            $isRead = filter_var($request->read, FILTER_VALIDATE_BOOLEAN);
            $query = $isRead ? $query->read() : $query->unread();
        }

        if ($request->has('type')) {
            $query->where('data->type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('data->title', 'like', "%{$search}%")
                    ->orWhere('data->body', 'like', "%{$search}%");
            });
        }

        $allowedOrderBy = ['created_at', 'read_at'];
        $orderBy = in_array((string) $request->get('order_by', 'created_at'), $allowedOrderBy, true)
            ? (string) $request->get('order_by', 'created_at')
            : 'created_at';
        $orderDirection = strtolower((string) $request->get('order_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($orderBy, $orderDirection);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationDataAsArray(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data) && $data !== '') {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get notification statistics
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();

        $allNotifications = $user->notifications();
        $unreadNotifications = $user->unreadNotifications();

        $total = $allNotifications->count();
        $unread = $unreadNotifications->count();

        // Count by type (in-memory; JSON path must not use dot form on the query builder)
        $typeCounts = $allNotifications->get()
            ->groupBy(function (DatabaseNotification $n) {
                $data = $this->notificationDataAsArray($n->data);

                return $data['type'] ?? 'unknown';
            })
            ->map(function ($notifications) {
                return [
                    'total' => $notifications->count(),
                    'unread' => $notifications->whereNull('read_at')->count(),
                ];
            });

        $recentTypes = $allNotifications->latest()
            ->take(5)
            ->get()
            ->map(function (DatabaseNotification $n) {
                $data = $this->notificationDataAsArray($n->data);

                return $data['type'] ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        $last = $allNotifications->latest()->first();

        return $this->success([
            'total' => $total,
            'unread' => $unread,
            'read' => max(0, $total - $unread),
            'by_type' => $typeCounts,
            'recent_types' => $recentTypes,
            'last_notification_at' => $last?->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Get a single notification
     */
    public function show(Notification $notification): JsonResponse
    {
        // Mark as read when viewing
        if (! $notification->read_at) {
            $notification->markAsRead();
        }

        return $this->success([
            'notification' => $this->transformNotification($notification, true),
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if (! $notification->read_at) {
            $notification->markAsRead();

            return $this->success([
                'message' => 'Notification marked as read',
                'data' => $this->transformNotification($notification),
            ]);
        }

        return $this->error('Notification is already read', 400);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(Notification $notification): JsonResponse
    {
        if ($notification->read_at) {
            $notification->markAsUnread();

            return $this->success([
                'message' => 'Notification marked as unread',
                'data' => $this->transformNotification($notification),
            ]);
        }

        return $this->error('Notification is already unread', 400);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $unreadCount = $user->unreadNotifications()->count();

        if ($unreadCount > 0) {
            $user->unreadNotifications()->update(['read_at' => now()]);

            return $this->success([
                'message' => "{$unreadCount} notifications marked as read",
            ]);
        }

        return $this->error('No unread notifications', 400);
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return $this->success([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Delete multiple notifications
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid request data', 422, $validator->errors());
        }

        $user = Auth::user();
        $deletedCount = $user->notifications()
            ->whereIn('id', $request->ids)
            ->delete();

        return $this->success([
            'message' => "{$deletedCount} notifications deleted successfully",
        ]);
    }

    /**
     * Clear all notifications
     */
    public function clearAll(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|in:read,unread,all',
            'confirmation' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return $this->error('Invalid request data', 422, $validator->errors());
        }}

        if (! $request->confirmation) {
            return $this->error('Please confirm you want to clear all notifications', 400);
        }

        $user = Auth::user();
        $type = $request->get('type', 'all');

        switch ($type) {
            case 'read':
                $count = $user->readNotifications()->count();
                $user->readNotifications()->delete();
                $message = "{$count} read notifications cleared";
                break;

            case 'unread':
                $count = $user->unreadNotifications()->count();
                $user->unreadNotifications()->delete();
                $message = "{$count} unread notifications cleared";
                break;

            case 'all':
            default:
                $count = $user->notifications()->count();
                $user->notifications()->delete();
                $message = "All {$count} notifications cleared";
                break;
        }

        return $this->success([
            'message' => $message,
        ]);
    }

    /**
     * Get notifications by type
     */
    public function byType(string $type): JsonResponse
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->where('data->type', $type)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $transformedNotifications = $notifications->map(function ($notification) {
            return $this->transformNotification($notification);
        });

        return $this->success([
            'type' => $type,
            'notifications' => $transformedNotifications,
            'total' => $notifications->total(),
            'unread' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = $user->unreadNotifications()->count();

        return $this->success([
            'count' => $count,
            'has_unread' => $count > 0,
        ],
        );
    }

    /**
     * Get recent notifications (last 24 hours)
     */
    public function recent(): JsonResponse
    {
        $user = Auth::user();

        $recentNotifications = $user->notifications()
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $transformedNotifications = $recentNotifications->map(function ($notification) {
            return $this->transformNotification($notification);
        });

        return $this->success([
            'notifications' => $transformedNotifications,
            'total_recent' => $recentNotifications->count(),
            'unread_recent' => $recentNotifications->whereNull('read_at')->count(),
        ]);
    }

    /**
     * Transform notification for API response
     */
    private function transformNotification(DatabaseNotification $notification, bool $detailed = false): array
    {
        $data = $this->notificationDataAsArray($notification->data);
        $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : 'unknown';
        $baseData = [
            'id' => $notification->id,
            'type' => $type,
            'title' => is_string($data['title'] ?? null) ? $data['title'] : 'Notification',
            'body' => is_string($data['body'] ?? null) ? $data['body'] : '',
            'action_url' => $data['action_url'] ?? null,
            'action_label' => is_string($data['action_label'] ?? null) ? $data['action_label'] : 'View',
            'is_read' => ! is_null($notification->read_at),
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString() ?? '',
            'created_at_human' => $notification->created_at?->diffForHumans() ?? '',
            'icon' => config("notification.{$type}", 'bell'),
            'priority' => is_string($data['priority'] ?? null) ? $data['priority'] : 'normal',
        ];

        if ($detailed) {
            $baseData['data'] = $data;
            $baseData['channels'] = $data['channels'] ?? ['database'];
            $baseData['metadata'] = $data['metadata'] ?? [];
            $baseData['expires_at'] = $data['expires_at'] ?? null;
        }

        return $baseData;
    }

}
