<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Placed = 'placed';
    case Processing = 'processing';
    case Shipping = 'shipping';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    
    public static function activeStatuses(): array
    {
        return [
            self::Placed->value,
            self::Processing->value,
            self::Shipping->value,
            self::OutForDelivery->value,
        ];
    }
}