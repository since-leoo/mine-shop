<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Application\Admin\Trade;

use App\Application\Admin\Trade\AppAfterSaleCommandService;
use App\Application\Admin\Trade\AppAfterSaleQueryService;
use App\Domain\Trade\AfterSale\Contract\AfterSaleActionInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReshipInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleQueryService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundService;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 * @coversNothing
 */
final class AppAfterSaleServicesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testQueryServiceReturnsRawPageResult(): void
    {
        $domainQuery = $this->createMock(DomainAfterSaleQueryService::class);
        $domainQuery->expects(self::once())
            ->method('pageForAdmin')
            ->with(['status' => 'pending_review'], 1, 10)
            ->willReturn([
                'list' => [['id' => 88, 'after_sale_no' => 'AS202603160088']],
                'total' => 1,
            ]);

        $service = new AppAfterSaleQueryService($domainQuery);
        $result = $service->page(['status' => 'pending_review'], 1, 10);

        self::assertSame(1, $result['total']);
        self::assertSame('AS202603160088', $result['list'][0]['after_sale_no']);
    }

    public function testQueryServiceReturnsRawDetailResult(): void
    {
        $detail = [
            'after_sale' => (object) ['id' => 91],
            'refund_record' => (object) ['refund_no' => 'RF202603160001'],
        ];

        $domainQuery = $this->createMock(DomainAfterSaleQueryService::class);
        $domainQuery->expects(self::once())
            ->method('detailForAdmin')
            ->with(91)
            ->willReturn($detail);

        $service = new AppAfterSaleQueryService($domainQuery);

        self::assertSame($detail, $service->detail(91));
    }

    public function testCommandServiceApproveUpdatesEntityAndReturnsRawDetail(): void
    {
        $entity = $this->makeEntity(id: 89, status: 'pending_review');
        $afterSaleService = $this->createMock(DomainAfterSaleService::class);
        $afterSaleService->expects(self::once())->method('getEntity')->with(89)->willReturn($entity);
        $afterSaleService->expects(self::once())
            ->method('saveEntity')
            ->with(self::callback(static function (AfterSaleEntity $saved): bool {
                return $saved->getId() === 89
                    && $saved->getStatus() === 'waiting_refund'
                    && $saved->getRefundAmount() === 9900;
            }))
            ->willReturn(true);

        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $queryService->expects(self::once())
            ->method('detail')
            ->with(89)
            ->willReturn(['after_sale' => (object) ['id' => 89], 'refund_record' => null]);

        $input = $this->createStub(AfterSaleReviewInput::class);
        $input->method('getId')->willReturn(89);
        $input->method('getApprovedRefundAmount')->willReturn(9900);

        $refundService = $this->createMock(DomainAfterSaleRefundService::class);
        $refundService->expects(self::never())->method('refund');

        $service = new AppAfterSaleCommandService(
            $afterSaleService,
            $queryService,
            $refundService,
            $this->makeMallSettingService(refundReview: true),
        );
        $result = $service->approve($input);

        self::assertSame(89, $result['after_sale']->id);
    }

    public function testCommandServiceApproveAutoRefundsWhenRefundReviewDisabled(): void
    {
        $entity = $this->makeEntity(id: 93, status: 'pending_review');
        $afterSaleService = $this->createMock(DomainAfterSaleService::class);
        $afterSaleService->expects(self::once())->method('getEntity')->with(93)->willReturn($entity);
        $afterSaleService->expects(self::exactly(2))
            ->method('saveEntity')
            ->with(self::callback(static fn (AfterSaleEntity $saved): bool => \in_array($saved->getStatus(), ['waiting_refund', 'refunding'], true)))
            ->willReturn(true);

        $refundService = $this->createMock(DomainAfterSaleRefundService::class);
        $refundService->expects(self::once())
            ->method('refund')
            ->with(self::callback(static fn (AfterSaleEntity $saved): bool => $saved->getStatus() === 'refunding'), 1, 'admin');

        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $queryService->expects(self::once())
            ->method('detail')
            ->with(93)
            ->willReturn(['after_sale' => (object) ['id' => 93], 'refund_record' => null]);

        $input = $this->createStub(AfterSaleReviewInput::class);
        $input->method('getId')->willReturn(93);
        $input->method('getApprovedRefundAmount')->willReturn(9900);
        $input->method('getOperatorId')->willReturn(1);
        $input->method('getOperatorName')->willReturn('admin');

        $service = new AppAfterSaleCommandService(
            $afterSaleService,
            $queryService,
            $refundService,
            $this->makeMallSettingService(refundReview: false),
        );
        $result = $service->approve($input);

        self::assertSame(93, $result['after_sale']->id);
    }

    public function testCommandServiceApproveMarksRefundFailedAndRethrowsWhenAutoRefundFails(): void
    {
        $entity = $this->makeEntity(id: 94, status: 'pending_review');
        $afterSaleService = $this->createMock(DomainAfterSaleService::class);
        $afterSaleService->expects(self::once())->method('getEntity')->with(94)->willReturn($entity);
        $afterSaleService->expects(self::exactly(3))
            ->method('saveEntity')
            ->with(self::callback(static fn (AfterSaleEntity $saved): bool => \in_array($saved->getStatus(), ['waiting_refund', 'refunding'], true)))
            ->willReturn(true);

        $refundService = $this->createMock(DomainAfterSaleRefundService::class);
        $refundService->expects(self::once())
            ->method('refund')
            ->willThrowException(new RuntimeException('gateway rejected'));

        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $queryService->expects(self::never())->method('detail');

        $input = $this->createStub(AfterSaleReviewInput::class);
        $input->method('getId')->willReturn(94);
        $input->method('getApprovedRefundAmount')->willReturn(9900);
        $input->method('getOperatorId')->willReturn(1);
        $input->method('getOperatorName')->willReturn('admin');

        $service = new AppAfterSaleCommandService(
            $afterSaleService,
            $queryService,
            $refundService,
            $this->makeMallSettingService(refundReview: false),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('gateway rejected');

        try {
            $service->approve($input);
        } finally {
            self::assertSame('waiting_refund', $entity->getStatus());
            self::assertSame('failed', $entity->getRefundStatus());
        }
    }

    public function testCommandServiceRefundTriggersRefundServiceAndReturnsRawDetail(): void
    {
        $entity = $this->makeEntity(id: 90, type: 'refund_only', status: 'waiting_refund');
        $afterSaleService = $this->createMock(DomainAfterSaleService::class);
        $afterSaleService->expects(self::once())->method('getEntity')->with(90)->willReturn($entity);
        $afterSaleService->expects(self::once())
            ->method('saveEntity')
            ->with(self::callback(static fn (AfterSaleEntity $saved): bool => $saved->getStatus() === 'refunding'))
            ->willReturn(true);

        $refundService = $this->createMock(DomainAfterSaleRefundService::class);
        $refundService->expects(self::once())
            ->method('refund')
            ->with(self::callback(static fn (AfterSaleEntity $saved): bool => $saved->getId() === 90), 1, 'admin');

        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $queryService->expects(self::once())
            ->method('detail')
            ->with(90)
            ->willReturn(['after_sale' => (object) ['id' => 90], 'refund_record' => null]);

        $input = $this->createStub(AfterSaleActionInput::class);
        $input->method('getId')->willReturn(90);
        $input->method('getOperatorId')->willReturn(1);
        $input->method('getOperatorName')->willReturn('admin');

        $service = new AppAfterSaleCommandService(
            $afterSaleService,
            $queryService,
            $refundService,
            $this->makeMallSettingService(),
        );
        $result = $service->refund($input);

        self::assertSame(90, $result['after_sale']->id);
    }

    public function testCommandServiceReshipUpdatesEntityAndReturnsRawDetail(): void
    {
        $entity = $this->makeEntity(id: 91, type: 'exchange', status: 'waiting_reship', returnStatus: 'seller_received');
        $afterSaleService = $this->createMock(DomainAfterSaleService::class);
        $afterSaleService->expects(self::once())->method('getEntity')->with(91)->willReturn($entity);
        $afterSaleService->expects(self::once())
            ->method('saveEntity')
            ->with(self::callback(static function (AfterSaleEntity $saved): bool {
                return $saved->getStatus() === 'reshipped'
                    && $saved->getReshipLogisticsCompany() === 'SF'
                    && $saved->getReshipLogisticsNo() === 'SF123456';
            }))
            ->willReturn(true);

        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $queryService->expects(self::once())
            ->method('detail')
            ->with(91)
            ->willReturn(['after_sale' => (object) ['id' => 91], 'refund_record' => null]);

        $input = $this->createStub(AfterSaleReshipInput::class);
        $input->method('getId')->willReturn(91);
        $input->method('getLogisticsCompany')->willReturn('SF');
        $input->method('getLogisticsNo')->willReturn('SF123456');

        $service = new AppAfterSaleCommandService(
            $afterSaleService,
            $queryService,
            $this->createMock(DomainAfterSaleRefundService::class),
            $this->makeMallSettingService(),
        );
        $result = $service->reship($input);

        self::assertSame(91, $result['after_sale']->id);
    }

    public function testCommandServiceCompleteExchangeMarksCompleted(): void
    {
        $entity = $this->makeEntity(id: 92, type: 'exchange', status: 'reshipped', returnStatus: 'seller_reshipped');
        $afterSaleService = $this->createMock(DomainAfterSaleService::class);
        $afterSaleService->expects(self::once())->method('getEntity')->with(92)->willReturn($entity);
        $afterSaleService->expects(self::once())
            ->method('saveEntity')
            ->with(self::callback(static fn (AfterSaleEntity $saved): bool => $saved->getStatus() === 'completed' && $saved->getReturnStatus() === 'buyer_received'))
            ->willReturn(true);

        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $queryService->expects(self::once())
            ->method('detail')
            ->with(92)
            ->willReturn(['after_sale' => (object) ['id' => 92], 'refund_record' => null]);

        $input = $this->createStub(AfterSaleActionInput::class);
        $input->method('getId')->willReturn(92);

        $service = new AppAfterSaleCommandService(
            $afterSaleService,
            $queryService,
            $this->createMock(DomainAfterSaleRefundService::class),
            $this->makeMallSettingService(),
        );
        $result = $service->completeExchange($input);

        self::assertSame(92, $result['after_sale']->id);
    }

    private function makeMallSettingService(bool $refundReview = true): DomainMallSettingService
    {
        $service = $this->createMock(DomainMallSettingService::class);
        $service->method('payment')->willReturn(new PaymentSetting(
            false,
            [],
            $refundReview,
            7,
            true,
            [],
        ));

        return $service;
    }

    private function makeEntity(
        int $id,
        string $type = 'refund_only',
        string $status = 'pending_review',
        string $refundStatus = 'pending',
        string $returnStatus = 'not_required',
    ): AfterSaleEntity {
        $entity = new AfterSaleEntity();
        $entity->setId($id);
        $entity->setAfterSaleNo('AS2026031600' . $id);
        $entity->setOrderId(10);
        $entity->setOrderItemId(20);
        $entity->setMemberId(1);
        $entity->setType($type);
        $entity->setStatus($status);
        $entity->setRefundStatus($refundStatus);
        $entity->setReturnStatus($returnStatus);
        $entity->setApplyAmount(18800);
        $entity->setRefundAmount(18800);
        $entity->setQuantity(1);
        $entity->setReason('size issue');
        $entity->setDescription('apply refund');
        $entity->setImages(['https://img.example/1.png']);

        return $entity;
    }
}
