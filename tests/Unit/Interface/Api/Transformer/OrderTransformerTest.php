<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Interface\Api\Transformer;

use App\Infrastructure\Model\Order\Order;
use App\Interface\Api\Transformer\OrderTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderTransformerTest extends TestCase
{
    public function testTransformReturnsReadableStatusAndButtons(): void
    {
        $order = new class([
            'id' => 1,
            'order_no' => 'ORD202603160001',
            'order_type' => 'normal',
            'status' => 'pending',
            'goods_amount' => 19900,
            'shipping_fee' => 0,
            'discount_amount' => 1000,
            'total_amount' => 18900,
            'pay_amount' => 18900,
            'pay_status' => 'unpaid',
            'pay_no' => '',
            'pay_method' => 'wechat',
            'buyer_remark' => '',
            'seller_remark' => '',
            'shipping_status' => 'pending',
            'package_count' => 0,
            'created_at' => new class {
                public function toDateTimeString(): string
                {
                    return '2026-03-16 10:00:00';
                }
            },
            'updated_at' => new class {
                public function toDateTimeString(): string
                {
                    return '2026-03-16 10:00:00';
                }
            },
        ]) extends Order {
            public function __construct(private array $payload = []) {}

            public function __get($key)
            {
                return $this->payload[$key] ?? null;
            }

            public function relationLoaded($key)
            {
                return false;
            }
        };

        $data = (new OrderTransformer())->transform($order);

        self::assertSame('待付款', $data['orderStatusName']);
        self::assertSame('取消订单', $data['buttonVOs'][0]['name']);
        self::assertSame('付款', $data['buttonVOs'][1]['name']);
    }
}
