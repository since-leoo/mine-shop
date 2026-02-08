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

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Factory\OrderTypeStrategyFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Plugin\Since\GroupBuy\Domain\Service\DomainGroupBuyOrderService;
use Plugin\Since\GroupBuy\Domain\Strategy\GroupBuyOrderStrategy;

/**
 * 策略注册集成测试.
 *
 * 验证 OrderTypeStrategyFactory 能正确解析 group_buy 类型并返回 GroupBuyOrderStrategy 实例。
 *
 * @internal
 * @coversNothing
 */
final class OrderTypeStrategyFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * 验证 OrderTypeStrategyFactory::make('group_buy') 返回 GroupBuyOrderStrategy 实例.
     *
     * Requirements: 10.2
     */
    public function testMakeGroupBuyReturnsGroupBuyOrderStrategy(): void
    {
        $snapshotService = \Mockery::mock(ProductSnapshotInterface::class);
        $groupBuyOrderService = \Mockery::mock(DomainGroupBuyOrderService::class);

        $strategy = new GroupBuyOrderStrategy($snapshotService, $groupBuyOrderService);

        $factory = new OrderTypeStrategyFactory([$strategy]);

        $resolved = $factory->make('group_buy');

        self::assertInstanceOf(GroupBuyOrderStrategy::class, $resolved);
        self::assertSame('group_buy', $resolved->type());
        self::assertSame($strategy, $resolved);
    }

    /**
     * 验证 make() 对不支持的订单类型抛出异常.
     */
    public function testMakeUnsupportedTypeThrowsException(): void
    {
        $factory = new OrderTypeStrategyFactory([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('不支持的订单类型：unknown');

        $factory->make('unknown');
    }
}
