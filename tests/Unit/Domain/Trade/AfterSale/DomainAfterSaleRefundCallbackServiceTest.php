<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\AfterSale;

use App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundCallbackService;
use App\Domain\Trade\Order\Repository\OrderPaymentRefundRepository;
use App\Infrastructure\Model\Order\OrderPaymentRefund;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @coversNothing
 */
final class DomainAfterSaleRefundCallbackServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testSuccessCallbackUpdatesRefundAndDispatchesEvent(): void
    {
        $repository = $this->createMock(OrderPaymentRefundRepository::class);
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $repository->expects(self::once())
            ->method('findByRefundNo')
            ->with('REF202603180001')
            ->willReturn($this->makeRefundModel());

        $repository->expects(self::once())
            ->method('updateByRefundNo')
            ->with(
                'REF202603180001',
                self::callback(static function (array $data): bool {
                    return $data['status'] === 'success'
                        && $data['third_party_refund_no'] === '500000001'
                        && isset($data['processed_at']);
                })
            )
            ->willReturn(true);

        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event): bool {
                return $event instanceof AfterSaleRefundSucceeded
                    && $event->afterSaleId === 90
                    && $event->orderId === 10
                    && $event->paymentNo === 'PAY202603170001'
                    && $event->paymentMethod === 'wechat';
            }));

        $afterSaleRepository->expects(self::never())->method('findById');

        $service = new DomainAfterSaleRefundCallbackService($repository, $afterSaleRepository, $dispatcher);
        $service->handleWechatRefundCallback([
            'out_refund_no' => 'REF202603180001',
            'refund_id' => '500000001',
            'refund_status' => 'SUCCESS',
            'success_time' => '2026-03-18T10:00:00+08:00',
            'amount' => ['refund' => 18800],
        ]);
    }

    public function testDuplicateSuccessCallbackIsIgnored(): void
    {
        $repository = $this->createMock(OrderPaymentRefundRepository::class);
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $repository->expects(self::once())
            ->method('findByRefundNo')
            ->with('REF202603180001')
            ->willReturn($this->makeRefundModel(status: 'success'));

        $repository->expects(self::never())->method('updateByRefundNo');
        $dispatcher->expects(self::never())->method('dispatch');

        $afterSaleRepository->expects(self::never())->method('findById');

        $service = new DomainAfterSaleRefundCallbackService($repository, $afterSaleRepository, $dispatcher);
        $service->handleWechatRefundCallback([
            'out_refund_no' => 'REF202603180001',
            'refund_status' => 'SUCCESS',
        ]);
    }

    public function testFailedCallbackMarksAfterSaleBackToWaitingRefund(): void
    {
        $repository = $this->createMock(OrderPaymentRefundRepository::class);
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $repository->expects(self::once())
            ->method('findByRefundNo')
            ->with('REF202603180001')
            ->willReturn($this->makeRefundModel());

        $repository->expects(self::once())
            ->method('updateByRefundNo')
            ->with(
                'REF202603180001',
                self::callback(static function (array $data): bool {
                    return $data['status'] === 'failed'
                        && $data['remark'] === 'ABNORMAL';
                })
            )
            ->willReturn(true);

        $afterSaleRepository->expects(self::once())
            ->method('findById')
            ->with(90)
            ->willReturn($this->makeAfterSaleModel());

        $afterSaleRepository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getStatus() === 'waiting_refund'
                    && $entity->getRefundStatus() === 'failed';
            }))
            ->willReturn(true);

        $dispatcher->expects(self::never())->method('dispatch');

        $service = new DomainAfterSaleRefundCallbackService($repository, $afterSaleRepository, $dispatcher);
        $service->handleWechatRefundCallback([
            'out_refund_no' => 'REF202603180001',
            'refund_id' => '500000001',
            'refund_status' => 'ABNORMAL',
        ]);
    }


    public function testFailedCallbackFallsBackToReadableRemarkWhenStatusMissing(): void
    {
        $repository = $this->createMock(OrderPaymentRefundRepository::class);
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $repository->expects(self::once())
            ->method('findByRefundNo')
            ->with('REF202603180001')
            ->willReturn($this->makeRefundModel());

        $repository->expects(self::once())
            ->method('updateByRefundNo')
            ->with(
                'REF202603180001',
                self::callback(static function (array $data): bool {
                    return $data['status'] === 'failed'
                        && $data['remark'] === '退款失败';
                })
            )
            ->willReturn(true);

        $afterSaleRepository->expects(self::once())
            ->method('findById')
            ->with(90)
            ->willReturn($this->makeAfterSaleModel());

        $afterSaleRepository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getStatus() === 'waiting_refund'
                    && $entity->getRefundStatus() === 'failed';
            }))
            ->willReturn(true);

        $dispatcher->expects(self::never())->method('dispatch');

        $service = new DomainAfterSaleRefundCallbackService($repository, $afterSaleRepository, $dispatcher);
        $service->handleWechatRefundCallback([
            'out_refund_no' => 'REF202603180001',
            'refund_id' => '500000001',
        ]);
    }

    private function makeRefundModel(string $status = 'refunding'): OrderPaymentRefund
    {
        $model = new class extends OrderPaymentRefund {
            public function __construct() {}
        };
        $model->setRawAttributes([
            'id' => 1,
            'refund_no' => 'REF202603180001',
            'payment_id' => 11,
            'payment_no' => 'PAY202603170001',
            'order_id' => 10,
            'order_no' => 'ORD202603170001',
            'member_id' => 1,
            'refund_amount' => 18800,
            'status' => $status,
            'operator_id' => 1,
            'operator_name' => 'admin',
            'extra_data' => '{"after_sale_id":90,"after_sale_no":"AS202603170090"}',
        ], true);

        return $model;
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
}
