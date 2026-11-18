<?php

declare(strict_types=1);

namespace HyperfTests\Integration\Catalog;

use App\Domain\Catalog\Product\Event\ProductCreated;
use App\Domain\Catalog\Product\Event\ProductDeleted;
use App\Domain\Catalog\Product\Event\ProductUpdated;
use App\Domain\Catalog\Product\ValueObject\ProductChangeVo;
use App\Domain\Catalog\Product\Listener\ProductSkuStockListener;
use App\Infrastructure\Service\Trade\RedisStockReservation;
use Hyperf\Config\Config;
use HyperfTests\Support\IsolatedRedis;
use PHPUnit\Framework\TestCase;

final class StockAdjustmentTest extends TestCase
{
    use IsolatedRedis;

    protected function tearDown(): void
    {
        $this->cleanRedis();
    }

    public function testEditsReplayAndReorderedAdjustmentsPreserveReservations(): void
    {
        $this->nativeRedis = $this->openRedis();
        $bridge = $this->redisBridge($this->nativeRedis);
        $config = new Config(['cache' => ['default' => ['prefix' => '']]]);
        $listener = new ProductSkuStockListener($bridge, $config);
        $stock = new RedisStockReservation($bridge, $config);
        $listener->process(new ProductCreated(1, [88], [['sku_id' => 88, 'stock' => 10]], 'create'));
        $stock->reserve('order', [['sku_id' => 88, 'quantity' => 3]], 'product:stock');
        $listener->process(new ProductCreated(1, [88], [['sku_id' => 88, 'stock' => 10]], 'create'));
        self::assertSame('7', $this->nativeRedis->hGet('product:stock', '88'));
        $listener->process(new ProductUpdated(1, new ProductChangeVo(1, stockAdjustments: [['sku_id' => 88, 'stock' => 12, 'delta' => -3]]), operationId: 'second-edit'));
        $listener->process(new ProductUpdated(1, new ProductChangeVo(1, stockAdjustments: [['sku_id' => 88, 'stock' => 15, 'delta' => 5]]), operationId: 'first-edit'));
        $listener->process(new ProductUpdated(1, new ProductChangeVo(1, stockAdjustments: [['sku_id' => 88, 'stock' => 15, 'delta' => 5]]), operationId: 'first-edit'));
        self::assertSame('9', $this->nativeRedis->hGet('product:stock', '88'));
        $stock->release('order', [['sku_id' => 88, 'quantity' => 3]], 'product:stock');
        self::assertSame('12', $this->nativeRedis->hGet('product:stock', '88'));
        $listener->process(new ProductCreated(2, [89], [['sku_id' => 89, 'stock' => 8]], 'create-other'));
        $listener->process(new ProductDeleted(1, [88, 89], 'delete'));
        $listener->process(new ProductUpdated(1, new ProductChangeVo(1, stockAdjustments: [['sku_id' => 88, 'stock' => 20, 'delta' => 8]]), operationId: 'late-edit'));
        $listener->process(new ProductCreated(1, [88], [['sku_id' => 88, 'stock' => 10]], 'late-create'));
        self::assertFalse($this->nativeRedis->hExists('product:stock', '88'));
        self::assertFalse($this->nativeRedis->hExists('product:stock', '89'));
    }

    public function testMissingStockNeverRecreatesPreviouslyReservedInventory(): void
    {
        $this->nativeRedis = $this->openRedis();
        $listener = new ProductSkuStockListener($this->redisBridge($this->nativeRedis), new Config(['cache' => ['default' => ['prefix' => '']]]));
        $this->expectException(\RuntimeException::class);
        $listener->process(new ProductUpdated(1, new ProductChangeVo(1, stockAdjustments: [['sku_id' => 88, 'stock' => 12, 'delta' => 2]]), operationId: 'edit-missing'));
    }
}
