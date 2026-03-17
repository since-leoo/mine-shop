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

namespace HyperfTests\Unit\Domain\Trade\AfterSale\Enum;

use App\Domain\Trade\AfterSale\Enum\AfterSaleRefundStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleReturnStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AfterSaleEnumsTest extends TestCase
{
    public function testAfterSaleTypeValues(): void
    {
        self::assertSame(['refund_only', 'return_refund', 'exchange'], AfterSaleType::values());
    }

    public function testAfterSaleStatusValues(): void
    {
        self::assertSame([
            'pending_review',
            'waiting_buyer_return',
            'waiting_seller_receive',
            'waiting_refund',
            'refunding',
            'waiting_reship',
            'reshipped',
            'completed',
            'closed',
        ], AfterSaleStatus::values());
    }

    public function testAfterSaleRefundStatusValues(): void
    {
        self::assertSame(['none', 'pending', 'processing', 'refunded'], AfterSaleRefundStatus::values());
    }

    public function testAfterSaleReturnStatusValues(): void
    {
        self::assertSame(['not_required', 'pending', 'buyer_shipped', 'seller_received', 'seller_reshipped', 'buyer_received'], AfterSaleReturnStatus::values());
    }
}
