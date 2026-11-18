<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Trade;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyOrderService;
use App\Domain\Trade\GroupBuy\Service\DomainGroupBuyService;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderStockService;
use PHPUnit\Framework\TestCase;

final class BusinessFailureBoundaryTest extends TestCase
{
    public function testMissingActivityIsBusinessFailureAndDatabaseFailureIsNotReclassified(): void
    {
        $repository = $this->createMock(GroupBuyRepository::class);
        $repository->method('findById')->willReturn(null);
        $service = new DomainGroupBuyOrderService(new DomainGroupBuyService($repository), $this->createMock(GroupBuyOrderRepository::class));
        try {
            $service->validateActivity(1, 1, 1, 7, null);
            self::fail('Missing activity must reject the order.');
        } catch (\DomainException $error) {
            self::assertSame('Group buy activity not found', $error->getMessage());
        }

        $failure = new \PDOException('database unavailable');
        $repository = $this->createMock(GroupBuyRepository::class);
        $repository->method('findById')->willThrowException($failure);
        $service = new DomainGroupBuyOrderService(new DomainGroupBuyService($repository), $this->createMock(GroupBuyOrderRepository::class));
        try {
            $service->validateActivity(1, 1, 1, 7, null);
            self::fail('Database failure must propagate for retry.');
        } catch (\PDOException $error) {
            self::assertSame($failure, $error);
        }
    }

    public function testStockLockContentionRemainsRetryableWhileEmptyPurchaseIsRejected(): void
    {
        $service = new DomainOrderStockService($this->createMock(DomainMallSettingService::class), lockRetry: 0);
        try {
            $service->acquireLocks([['sku_id' => 1, 'quantity' => 1]]);
            self::fail('Unavailable inventory lock must remain retryable.');
        } catch (\RuntimeException $error) {
            self::assertSame('库存繁忙，请稍后重试', $error->getMessage());
        }
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('没有可扣减的商品');
        $service->reserve([]);
    }

    public function testTemporarilyMissingPaymentRemainsAnInternalRetryableFailure(): void
    {
        $repository = $this->createMock(OrderPaymentRepository::class);
        $repository->method('findByPaymentNo')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('支付记录不存在: payment_no=WAITING');
        (new DomainOrderPaymentService($repository))->getEntityByPaymentNo('WAITING');
    }
}
