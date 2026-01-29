<?php

declare(strict_types=1);

namespace App\Domain\Order\Enum;

enum ShippingStatus: string
{
    case PENDING = 'pending';
    case PARTIAL_SHIPPED = 'partial_shipped';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
