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

namespace HyperfTests\Feature\Domain\Order;

use App\Application\Mapper\OrderAssembler;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderAssemblerTest extends TestCase
{
    public function testToSubmitCommandBuildsEntityWithItems(): void
    {
        $payload = [
            'member_id' => 99,
            'order_type' => 'normal',
            'items' => [
                [
                    'sku_id' => 5,
                    'quantity' => 3,
                    'unit_price' => 99.9,
                    'product_name' => '测试商品',
                ],
            ],
            'address' => [
                'name' => '张三',
                'phone' => '13800138000',
                'province' => '广东',
                'city' => '广州',
                'district' => '天河',
                'detail' => '体育西路',
            ],
            'remark' => '请尽快发货',
        ];

        $entity = OrderAssembler::toSubmitCommand($payload);

        self::assertSame(99, $entity->getMemberId());
        self::assertSame('normal', $entity->getOrderType());
        self::assertCount(1, $entity->getItems());
        $item = $entity->getItems()[0];
        self::assertSame(5, $item->getSkuId());
        self::assertSame(3, $item->getQuantity());
        self::assertSame('测试商品', $item->getProductName());
        self::assertSame('张三', $entity->getAddress()?->getReceiverName());
        self::assertSame('请尽快发货', $entity->getBuyerRemark());
    }
}
