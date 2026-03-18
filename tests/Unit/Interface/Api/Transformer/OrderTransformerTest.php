<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Interface\Api\Transformer;

use App\Infrastructure\Model\Order\Order;
use App\Interface\Api\Transformer\OrderTransformer;
use Hyperf\Collection\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderTransformerTest extends TestCase
{
    public function testTransformReturnsExpectedPendingButtons(): void
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

        self::assertSame('pending', $data['status']);
        self::assertSame([2, 1], array_column($data['buttonVOs'], 'type'));
    }

    public function testTransformHidesCommentButtonWhenAllItemsReviewed(): void
    {
        $review = new class {
        };

        $reviewedItem = new class([
            'id' => 101,
            'product_id' => 2001,
            'sku_id' => 3001,
            'product_name' => 'pet-food',
            'sku_name' => 'adult-2kg',
            'product_image' => 'https://example.com/pet-food.jpg',
            'unit_price' => 19900,
            'quantity' => 1,
            'total_price' => 19900,
            'review' => $review,
        ]) {
            public function __construct(private array $payload = []) {}

            public function __get($key)
            {
                return $this->payload[$key] ?? null;
            }

            public function relationLoaded($key)
            {
                return $key === 'review';
            }
        };

        $order = new class([
            'id' => 2,
            'order_no' => 'ORD202603180001',
            'order_type' => 'normal',
            'status' => 'completed',
            'goods_amount' => 19900,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total_amount' => 19900,
            'pay_amount' => 19900,
            'pay_status' => 'paid',
            'pay_no' => 'PAY202603180001',
            'pay_method' => 'wechat',
            'buyer_remark' => '',
            'seller_remark' => '',
            'shipping_status' => 'delivered',
            'package_count' => 1,
            'items' => new Collection([$reviewedItem]),
            'created_at' => new class {
                public function toDateTimeString(): string
                {
                    return '2026-03-18 10:00:00';
                }
            },
            'updated_at' => new class {
                public function toDateTimeString(): string
                {
                    return '2026-03-18 10:00:00';
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
                return $key === 'items';
            }
        };

        $data = (new OrderTransformer())->transform($order);

        self::assertSame('completed', $data['status']);
        self::assertSame([9], array_column($data['buttonVOs'], 'type'));
    }
}
