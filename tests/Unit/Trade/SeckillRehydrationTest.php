<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
use App\Domain\Trade\Seckill\Repository\SeckillProductRepository;
use App\Domain\Trade\Seckill\Repository\SeckillSessionRepository;
use App\Domain\Trade\Seckill\Service\SeckillCacheService;
use App\Domain\Trade\Seckill\Strategy\SeckillOrderStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class SeckillRehydrationTest extends TestCase
{
    public function testEmptyOrderDoesNotReadAnAbsentItemOrResolveTheCache(): void
    {
        $strategy = new SeckillOrderStrategy(
            $this->createMock(ProductSnapshotInterface::class),
            $this->createMock(SeckillCacheService::class),
            $this->createMock(SeckillSessionRepository::class),
            $this->createMock(SeckillProductRepository::class),
            $this->createMock(SeckillOrderRepository::class),
        );
        $order = new OrderEntity();
        $order->setExtra('session_id', 1);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())->method('get');
        $strategy->rehydrate($order, $container);
        self::assertNull($order->getExtra('seckill_product_entity'));
    }
}
