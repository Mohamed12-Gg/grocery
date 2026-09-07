<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_delivery_updates' => [
                'category' => 'Order & Delivery Updates',
                'enabled' => $this->order_confirmation || $this->order_shipped || $this->delivery_updates || $this->out_of_stock_alerts,
                'settings' => [
                    'order_confirmation' => $this->order_confirmation,
                    'order_shipped' => $this->order_shipped,
                    'delivery_updates' => $this->delivery_updates,
                    'out_of_stock_alerts' => $this->out_of_stock_alerts,
                ],
            ],
            'deals_promotions' => [
                'category' => 'Deals & Promotions',
                'enabled' => $this->weekly_discounts || $this->exclusive_member_offers || $this->seasonal_campaigns,
                'settings' => [
                    'weekly_discounts' => $this->weekly_discounts,
                    'exclusive_member_offers' => $this->exclusive_member_offers,
                    'seasonal_campaigns' => $this->seasonal_campaigns,
                ],
            ],
            'account_reminders' => [
                'category' => 'Account & Reminders',
                'enabled' => $this->cart_reminders || $this->payment_billing,
                'settings' => [
                    'cart_reminders' => $this->cart_reminders,
                    'payment_billing' => $this->payment_billing,
                ],
            ],
            'channels' => [
                'category' => 'Notification Channels',
                'enabled' => $this->email_notifications || $this->push_notifications || $this->sms_notifications,
                'settings' => [
                    'email_notifications' => $this->email_notifications,
                    'push_notifications' => $this->push_notifications,
                    'sms_notifications' => $this->sms_notifications,
                ],
            ],
        ];
    }
}
