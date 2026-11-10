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

namespace HyperfTests\Unit\Distribution;

use PHPUnit\Framework\TestCase;
use Plugin\Distribution\Application\AppDistributionTradeService;
use Plugin\Distribution\Domain\Service\DomainCommissionService;

/**
 * @internal
 * @coversNothing
 */
final class SettlementBatchTest extends TestCase
{
    public function testOneFailedOrderDoesNotPreventOtherDueOrdersFromSettling(): void
    {
        $commissions = $this->createMock(DomainCommissionService::class);
        $commissions->method('pendingOrders')->willReturn([11, 12, 13]);
        $trade = $this->getMockBuilder(AppDistributionTradeService::class)
            ->setConstructorArgs([$commissions])->onlyMethods(['completed'])->getMock();
        $visited = [];
        $trade->expects(self::exactly(3))->method('completed')->willReturnCallback(static function (int $id) use (&$visited): void {
            $visited[] = $id;
            if ($id === 11) {
                throw new \DomainException('账目待核对');
            }
        });

        self::assertSame([['order_id' => 11, 'error' => '账目待核对']], $trade->settleDue());
        self::assertSame([11, 12, 13], $visited);
    }
}
