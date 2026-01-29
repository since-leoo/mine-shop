<?php

declare(strict_types=1);

namespace HyperfTests\Feature\Domain\Order;

use App\Domain\Order\Entity\OrderSubmitCommand;
use App\Domain\Order\Strategy\NormalOrderStrategy;
use App\Domain\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class NormalOrderStrategyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildDraftSuccess(): void
    {
        $strategy = $this->makeStrategy([
            1 => $this->makeSnapshot([
                'id' => 1,
                'product_id' => 9,
                'sku_name' => '标准款',
                'sale_price' => 100,
                'weight' => 1.5,
            ]),
        ]);

        $command = $this->makeCommand();
        $draft = $strategy->buildDraft($command);

        self::assertSame('normal', $draft->getOrderType());
        self::assertSame(1, \count($draft->getItems()));
        $item = $draft->getItems()[0];
        self::assertSame(1, $item->getSkuId());
        self::assertSame(2, $item->getQuantity());
        self::assertSame(200.0, $item->getTotalPrice());
        self::assertSame(200.0, $draft->getPriceDetail()?->getGoodsAmount());
        self::assertSame(0.0, $draft->getPriceDetail()?->getShippingFee());
    }

    public function testValidateRequiresAddress(): void
    {
        $strategy = $this->makeStrategy([]);
        $command = $this->makeCommand([
            'address' => ['name' => '', 'phone' => '', 'detail' => ''],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('请完善收货地址信息');
        $strategy->validate($command);
    }

    public function testBuildDraftFailsWhenSkuInactive(): void
    {
        $strategy = $this->makeStrategy([
            1 => $this->makeSnapshot([
                'id' => 1,
                'sku_name' => '标准款',
                'status' => ProductSku::STATUS_INACTIVE,
            ]),
        ]);
        $command = $this->makeCommand([
            'items' => [
                ['sku_id' => 1, 'quantity' => 5],
            ],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('商品 标准款 已下架');
        $strategy->buildDraft($command);
    }

    private function makeCommand(array $overrides = []): OrderSubmitCommand
    {
        $defaults = [
            'member_id' => 1,
            'order_type' => 'normal',
            'items' => [
                ['sku_id' => 1, 'quantity' => 2],
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

        $data = array_merge($defaults, $overrides);

        $command = new OrderSubmitCommand();
        $command->setMemberId($data['member_id']);
        $command->setOrderType($data['order_type']);
        $command->setItems($data['items']);
        $command->setAddress($data['address']);
        $command->setBuyerRemark($data['remark']);
        return $command;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function makeSnapshot(array $attributes): array
    {
        $skuId = $attributes['id'] ?? 1;
        return [
            'product_id' => $attributes['product_id'] ?? 1,
            'product_name' => $attributes['product_name'] ?? '测试商品',
            'product_status' => $attributes['product_status'] ?? Product::STATUS_ACTIVE,
            'product_image' => $attributes['product_image'] ?? null,
            'sku_id' => $skuId,
            'sku_name' => $attributes['sku_name'] ?? '默认规格',
            'sku_status' => $attributes['status'] ?? ProductSku::STATUS_ACTIVE,
            'sku_image' => $attributes['image'] ?? null,
            'spec_values' => $attributes['spec_values'] ?? [],
            'sale_price' => (float) ($attributes['sale_price'] ?? 99.0),
            'weight' => (float) ($attributes['weight'] ?? 1.0),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $snapshots
     */
    private function makeStrategy(array $snapshots): NormalOrderStrategy
    {
        $service = Mockery::mock(ProductSnapshotInterface::class);
        $service->shouldReceive('getSkuSnapshots')
            ->andReturnUsing(static function (array $ids) use ($snapshots): array {
                $result = [];
                foreach ($ids as $id) {
                    if (isset($snapshots[$id])) {
                        $result[$id] = $snapshots[$id];
                    }
                }
                return $result;
            });

        return new NormalOrderStrategy($service);
    }
}
