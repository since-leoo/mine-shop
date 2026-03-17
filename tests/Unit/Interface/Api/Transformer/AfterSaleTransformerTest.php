<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Unit\Interface\Api\Transformer;

use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use App\Interface\Api\Transformer\AfterSaleTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AfterSaleTransformerTest extends TestCase
{
    public function testTransformBuildsApiPayload(): void
    {
        $afterSale = new class extends AfterSale {
            public function __construct() {}
        };
        $order = new class extends Order {
            public function __construct() {}
        };
        $orderItem = new class extends OrderItem {
            public function __construct() {}
        };

        $afterSale->setRawAttributes([
            'id' => 1,
            'after_sale_no' => 'AS202603160001',
            'order_id' => 10,
            'order_item_id' => 20,
            'type' => 'refund_only',
            'status' => 'pending_review',
            'refund_status' => 'pending',
            'return_status' => 'not_required',
            'apply_amount' => 100,
            'refund_amount' => 100,
            'quantity' => 1,
            'reason' => '?????',
            'description' => '??',
            'reject_reason' => '???????????',
            'images' => '["a.png"]',
            'buyer_return_logistics_company' => '??',
            'buyer_return_logistics_no' => 'SF1234567890',
        ], true);

        $order->order_no = 'ORD202603160001';
        $orderItem->product_id = 200;
        $orderItem->sku_id = 300;
        $orderItem->product_name = '????';
        $orderItem->sku_name = '????';
        $orderItem->product_image = 'cover.png';

        $afterSale->setRelation('order', $order);
        $afterSale->setRelation('orderItem', $orderItem);

        $data = (new AfterSaleTransformer())->transform($afterSale);

        self::assertSame('AS202603160001', $data['afterSaleNo']);
        self::assertSame('ORD202603160001', $data['orderNo']);
        self::assertSame('????', $data['product']['productName']);
        self::assertSame(['a.png'], $data['images']);
        self::assertSame('???????????', $data['rejectReason']);
        self::assertSame('??', $data['buyerReturnLogisticsCompany']);
        self::assertSame('SF1234567890', $data['buyerReturnLogisticsNo']);
    }
}
