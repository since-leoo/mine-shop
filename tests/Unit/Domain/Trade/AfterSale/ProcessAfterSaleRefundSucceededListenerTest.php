<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\AfterSale;

use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Service\DomainMemberWalletService;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\AfterSale\Listener\ProcessAfterSaleRefundSucceededListener;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Domain\Trade\Order\Service\DomainOrderPaymentService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\OrderPayment;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
final class ProcessAfterSaleRefundSucceededListenerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testProcessBalanceRefundUpdatesStatusAndDispatchesWalletEvent(): void
    {
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $orderPaymentService = $this->createMock(DomainOrderPaymentService::class);
        $orderPaymentRepository = $this->createMock(OrderPaymentRepository::class);
        $orderService = $this->createMock(DomainOrderService::class);
        $walletService = $this->createMock(DomainMemberWalletService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $afterSaleRepository->expects(self::once())
            ->method('findById')
            ->with(90)
            ->willReturn($this->makeAfterSaleModel());

        $afterSaleRepository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function (AfterSaleEntity $entity): bool {
                return $entity->getStatus() === 'completed' && $entity->getRefundStatus() === 'refunded';
            }))
            ->willReturn(true);

        $orderPaymentService->expects(self::once())
            ->method('addRefundAmount')
            ->with('PAY202603170001', 18800)
            ->willReturn($this->makeOrderPaymentModel(status: 'refunded', refundAmount: 18800));

        $orderPaymentRepository->expects(self::never())
            ->method('findByPaymentNo');

        $walletEntity = new \App\Domain\Member\Entity\MemberWalletEntity();
        $walletEntity->setId(8);
        $walletEntity->setMemberId(1);
        $walletEntity->setType('balance');
        $walletEntity->setBalance(1000);

        $walletService->expects(self::once())
            ->method('getEntity')
            ->with(1, 'balance')
            ->willReturn($walletEntity);
        $walletService->expects(self::once())
            ->method('saveEntity')
            ->with($walletEntity);

        $orderEntity = $this->createMock(OrderEntity::class);
        $orderEntity->expects(self::once())->method('setPayStatus')->with('refunded');
        $orderEntity->expects(self::once())->method('setStatus')->with('refunded');
        $orderService->expects(self::once())->method('getEntity')->with(10)->willReturn($orderEntity);
        $orderService->expects(self::once())->method('update')->with($orderEntity)->willReturn(true);

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(MemberBalanceAdjusted::class));

        $listener = new ProcessAfterSaleRefundSucceededListener(
            $afterSaleRepository,
            $orderPaymentService,
            $orderPaymentRepository,
            $orderService,
            $walletService,
            $dispatcher,
        );

        $listener->process(new AfterSaleRefundSucceeded(90, 10, 1, 18800, 11, 'PAY202603170001', 'balance', 'REF202603170001', 1, 'admin'));

        self::assertTrue(true);
    }

    private function makeAfterSaleModel(): AfterSale
    {
        $model = new class extends AfterSale {
            public function __construct() {}
        };
        $model->setRawAttributes([
            'id' => 90,
            'after_sale_no' => 'AS202603170090',
            'order_id' => 10,
            'order_item_id' => 20,
            'member_id' => 1,
            'type' => 'refund_only',
            'status' => 'refunding',
            'refund_status' => 'processing',
            'return_status' => 'not_required',
            'apply_amount' => 18800,
            'refund_amount' => 18800,
            'quantity' => 1,
            'reason' => 'size issue',
            'images' => '[]',
        ], true);

        return $model;
    }

    private function makeOrderPaymentModel(string $paymentMethod = 'balance', string $status = 'paid', int $refundAmount = 0): OrderPayment
    {
        $model = new class extends OrderPayment {
            public function __construct() {}
        };
        $model->setRawAttributes([
            'id' => 11,
            'payment_no' => 'PAY202603170001',
            'order_id' => 10,
            'order_no' => 'ORD202603170001',
            'member_id' => 1,
            'payment_method' => $paymentMethod,
            'payment_amount' => 18800,
            'paid_amount' => 18800,
            'refund_amount' => $refundAmount,
            'status' => $status,
        ], true);

        return $model;
    }
}
