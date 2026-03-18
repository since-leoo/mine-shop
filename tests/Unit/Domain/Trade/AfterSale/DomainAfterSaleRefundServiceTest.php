<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\AfterSale;

use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Domain\Trade\Order\Repository\OrderPaymentRepository;
use App\Infrastructure\Model\Order\OrderPayment;
use App\Infrastructure\Service\Pay\YsdPayService;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
final class DomainAfterSaleRefundServiceTest extends TestCase
{
    public function testBalanceRefundCreatesRefundRecordAndDispatchesSuccessEvent(): void
    {
        $paymentRepository = $this->createMock(OrderPaymentRepository::class);
        $paymentRefundRepository = $this->createMock(OrderPaymentRefundRepository::class);
        $payService = $this->createMock(YsdPayService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $paymentRepository->expects(self::once())
            ->method('findByOrderId')
            ->with(10)
            ->willReturn($this->makePaymentModel(paymentMethod: 'balance'));

        $paymentRefundRepository->expects(self::once())
            ->method('create')
            ->with(self::callback(static function (array $data): bool {
                return $data['payment_no'] === 'PAY202603170001'
                    && $data['refund_amount'] === 18800
                    && $data['status'] === 'refunding';
            }));

        $paymentRefundRepository->expects(self::once())
            ->method('updateByRefundNo')
            ->with(
                self::isType('string'),
                self::callback(static function (array $data): bool {
                    return $data['status'] === 'success' && isset($data['processed_at']);
                })
            )
            ->willReturn(true);

        $payService->expects(self::never())->method('refund');

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof AfterSaleRefundSucceeded
                    && $event->orderId === 10
                    && $event->memberId === 1
                    && $event->refundAmount === 18800
                    && $event->paymentMethod === 'balance';
            }));

        $service = new DomainAfterSaleRefundService($paymentRepository, $paymentRefundRepository, $payService, $dispatcher);
        $status = $service->refund($this->makeAfterSaleEntity(), 1, 'admin');

        self::assertSame('success', $status);
    }

    public function testWechatRefundCallsGatewayAndWaitsForCallback(): void
    {
        $paymentRepository = $this->createMock(OrderPaymentRepository::class);
        $paymentRefundRepository = $this->createMock(OrderPaymentRefundRepository::class);
        $payService = $this->createMock(YsdPayService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $paymentRepository->expects(self::once())
            ->method('findByOrderId')
            ->with(10)
            ->willReturn($this->makePaymentModel(paymentMethod: 'wechat'));

        $paymentRefundRepository->expects(self::once())
            ->method('create');

        $payService->expects(self::once())
            ->method('refund')
            ->with(
                self::callback(static function (array $payload): bool {
                    return $payload['out_trade_no'] === 'ORD202603170001'
                        && $payload['amount']['refund'] === 18800
                        && $payload['amount']['total'] === 18800;
                }),
                self::isType('array'),
                'mini'
            )
            ->willReturn([
                'status' => 'SUCCESS',
                'refund_id' => '500000001',
            ]);

        $paymentRefundRepository->expects(self::once())
            ->method('updateByRefundNo')
            ->with(
                self::isType('string'),
                self::callback(static function (array $data): bool {
                    return $data['status'] === 'refunding'
                        && $data['third_party_refund_no'] === '500000001';
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::never())
            ->method('dispatch');

        $service = new DomainAfterSaleRefundService($paymentRepository, $paymentRefundRepository, $payService, $dispatcher);
        $status = $service->refund($this->makeAfterSaleEntity(), 1, 'admin');

        self::assertSame('processing', $status);
    }


    public function testRefundThrowsReadableMessageWhenPaymentRecordMissing(): void
    {
        $paymentRepository = $this->createMock(OrderPaymentRepository::class);
        $paymentRefundRepository = $this->createMock(OrderPaymentRefundRepository::class);
        $payService = $this->createMock(YsdPayService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $paymentRepository->expects(self::once())
            ->method('findByOrderId')
            ->with(10)
            ->willReturn(null);

        $paymentRefundRepository->expects(self::never())->method('create');
        $payService->expects(self::never())->method('refund');
        $dispatcher->expects(self::never())->method('dispatch');

        $service = new DomainAfterSaleRefundService($paymentRepository, $paymentRefundRepository, $payService, $dispatcher);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('订单支付记录不存在');
        $service->refund($this->makeAfterSaleEntity(), 1, 'admin');
    }

    public function testWechatRefundFailureFallsBackToReadableMessage(): void
    {
        $paymentRepository = $this->createMock(OrderPaymentRepository::class);
        $paymentRefundRepository = $this->createMock(OrderPaymentRefundRepository::class);
        $payService = $this->createMock(YsdPayService::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $paymentRepository->expects(self::once())
            ->method('findByOrderId')
            ->with(10)
            ->willReturn($this->makePaymentModel(paymentMethod: 'wechat'));

        $paymentRefundRepository->expects(self::once())
            ->method('create');

        $payService->expects(self::once())
            ->method('refund')
            ->willReturn([
                'status' => 'FAILED',
            ]);

        $paymentRefundRepository->expects(self::once())
            ->method('updateByRefundNo')
            ->with(
                self::isType('string'),
                self::callback(static function (array $data): bool {
                    return $data['status'] === 'failed'
                        && $data['remark'] === '原路退款失败';
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::never())->method('dispatch');

        $service = new DomainAfterSaleRefundService($paymentRepository, $paymentRefundRepository, $payService, $dispatcher);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('原路退款失败');
        $service->refund($this->makeAfterSaleEntity(), 1, 'admin');
    }

    private function makeAfterSaleEntity(): AfterSaleEntity
    {
        $entity = new AfterSaleEntity();
        $entity->setId(90);
        $entity->setAfterSaleNo('AS202603170090');
        $entity->setOrderId(10);
        $entity->setOrderItemId(20);
        $entity->setMemberId(1);
        $entity->setType('refund_only');
        $entity->setStatus('refunding');
        $entity->setRefundStatus('processing');
        $entity->setReturnStatus('not_required');
        $entity->setApplyAmount(18800);
        $entity->setRefundAmount(18800);
        $entity->setQuantity(1);
        $entity->setReason('size issue');

        return $entity;
    }

    private function makePaymentModel(string $paymentMethod): OrderPayment
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
            'refund_amount' => 0,
            'status' => 'paid',
        ], true);

        return $model;
    }
}
