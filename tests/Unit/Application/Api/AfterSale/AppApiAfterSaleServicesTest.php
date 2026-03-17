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

namespace HyperfTests\Unit\Application\Api\AfterSale;

use App\Application\Api\AfterSale\AppApiAfterSaleCommandService;
use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;
use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReturnShipmentInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use App\Infrastructure\Model\AfterSale\AfterSale;
use DG\BypassFinals;
use Hyperf\Paginator\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AppApiAfterSaleServicesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testQueryServiceReturnsEligibilityResult(): void
    {
        $domainService = $this->createMock(DomainAfterSaleService::class);
        $repository = $this->createMock(AfterSaleRepository::class);

        $domainService->expects(self::once())
            ->method('eligibility')
            ->with(1, 10, 20)
            ->willReturn(['can_apply' => true, 'types' => ['refund_only'], 'max_quantity' => 1, 'max_amount' => 100]);

        $service = new AppApiAfterSaleQueryService($domainService, $repository);
        $result = $service->eligibility(1, 10, 20);

        self::assertTrue($result['can_apply']);
        self::assertSame(['refund_only'], $result['types']);
    }

    public function testQueryServiceReturnsPaginatorAndDetail(): void
    {
        $domainService = $this->createMock(DomainAfterSaleService::class);
        $repository = $this->createMock(AfterSaleRepository::class);
        $model = $this->makeAfterSaleModel();
        $paginator = new LengthAwarePaginator([$model], 1, 10, 1);

        $repository->expects(self::once())
            ->method('paginateByMember')
            ->with(1, 'all', 1, 10)
            ->willReturn($paginator);

        $repository->expects(self::once())
            ->method('findByIdAndMember')
            ->with(88, 1)
            ->willReturn($model);

        $service = new AppApiAfterSaleQueryService($domainService, $repository);

        self::assertSame($paginator, $service->paginateByMember(1, 'all', 1, 10));
        self::assertSame($model, $service->detail(1, 88));
    }

    public function testCommandServiceApplyReturnsPersistedModel(): void
    {
        $domainService = $this->createMock(DomainAfterSaleService::class);
        $repository = $this->createMock(AfterSaleRepository::class);
        $input = $this->createMock(AfterSaleApplyInput::class);
        $entity = AfterSaleEntity::apply($this->makeInput());
        $entity->setId(66);
        $model = $this->makeAfterSaleModel();

        $domainService->expects(self::once())
            ->method('apply')
            ->with($input)
            ->willReturn($entity);

        $repository->expects(self::once())
            ->method('findById')
            ->with(66)
            ->willReturn($model);

        $service = new AppApiAfterSaleCommandService($domainService, $repository);

        self::assertSame($model, $service->apply($input));
    }

    public function testCommandServiceCancelUpdatesEntity(): void
    {
        $domainService = $this->createMock(DomainAfterSaleService::class);
        $repository = $this->createMock(AfterSaleRepository::class);
        $model = $this->makeAfterSaleModel();

        $repository->expects(self::once())
            ->method('findByIdAndMember')
            ->with(88, 1)
            ->willReturn($model);

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function (AfterSaleEntity $entity): bool {
                return $entity->getId() === 88 && $entity->getStatus() === 'closed';
            }))
            ->willReturn(true);

        $service = new AppApiAfterSaleCommandService($domainService, $repository);
        $service->cancel(1, 88);

        self::assertTrue(true);
    }

    public function testCommandServiceSubmitReturnShipmentUpdatesEntity(): void
    {
        $domainService = $this->createMock(DomainAfterSaleService::class);
        $repository = $this->createMock(AfterSaleRepository::class);
        $model = $this->makeAfterSaleModel(status: 'waiting_buyer_return', type: 'return_refund', returnStatus: 'pending');
        $input = new class implements AfterSaleReturnShipmentInput {
            public function getId(): int { return 88; }
            public function getMemberId(): int { return 1; }
            public function getLogisticsCompany(): string { return '??'; }
            public function getLogisticsNo(): string { return 'SF1234567890'; }
        };

        $repository->expects(self::once())
            ->method('findByIdAndMember')
            ->with(88, 1)
            ->willReturn($model);

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function (AfterSaleEntity $entity): bool {
                return $entity->getId() === 88
                    && $entity->getStatus() === 'waiting_seller_receive'
                    && $entity->getBuyerReturnLogisticsCompany() === '??'
                    && $entity->getBuyerReturnLogisticsNo() === 'SF1234567890';
            }))
            ->willReturn(true);

        $service = new AppApiAfterSaleCommandService($domainService, $repository);
        $service->submitReturnShipment($input);

        self::assertTrue(true);
    }

    public function testCommandServiceConfirmExchangeReceivedUpdatesEntity(): void
    {
        $domainService = $this->createMock(DomainAfterSaleService::class);
        $repository = $this->createMock(AfterSaleRepository::class);
        $model = $this->makeAfterSaleModel(status: 'reshipped', type: 'exchange', returnStatus: 'seller_reshipped');
        $model->reship_logistics_company = '??';
        $model->reship_logistics_no = 'YT0001';

        $repository->expects(self::once())
            ->method('findByIdAndMember')
            ->with(88, 1)
            ->willReturn($model);

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function (AfterSaleEntity $entity): bool {
                return $entity->getId() === 88
                    && $entity->getStatus() === 'completed'
                    && $entity->getReturnStatus() === 'buyer_received';
            }))
            ->willReturn(true);

        $service = new AppApiAfterSaleCommandService($domainService, $repository);
        $service->confirmExchangeReceived(1, 88);

        self::assertTrue(true);
    }

    private function makeAfterSaleModel(
        string $status = 'pending_review',
        string $type = 'refund_only',
        string $returnStatus = 'not_required'
    ): AfterSale
    {
        $model = new class extends AfterSale {
            public function __construct() {}
        };
        $model->id = 88;
        $model->after_sale_no = 'AS202603160088';
        $model->order_id = 10;
        $model->order_item_id = 20;
        $model->member_id = 1;
        $model->type = $type;
        $model->status = $status;
        $model->refund_status = 'pending';
        $model->return_status = $returnStatus;
        $model->apply_amount = 100;
        $model->refund_amount = 100;
        $model->quantity = 1;
        $model->reason = '尺寸不合适';
        $model->description = '测试';
        $model->images = [];

        return $model;
    }

    private function makeInput(): AfterSaleApplyInput
    {
        return new class implements AfterSaleApplyInput {
            public function getOrderId(): int { return 10; }
            public function getOrderItemId(): int { return 20; }
            public function getMemberId(): int { return 1; }
            public function getType(): string { return 'refund_only'; }
            public function getReason(): string { return '尺寸不合适'; }
            public function getDescription(): ?string { return '测试'; }
            public function getApplyAmount(): int { return 100; }
            public function getQuantity(): int { return 1; }
            public function getImages(): ?array { return []; }
        };
    }
}