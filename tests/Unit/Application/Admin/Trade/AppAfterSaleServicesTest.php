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

namespace HyperfTests\Unit\Application\Admin\Trade;

use App\Application\Admin\Trade\AppAfterSaleCommandService;
use App\Application\Admin\Trade\AppAfterSaleQueryService;
use App\Domain\Trade\AfterSale\Contract\AfterSaleActionInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReshipInput;
use App\Domain\Trade\AfterSale\Contract\AfterSaleReviewInput;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

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

    public function testQueryServiceReturnsFormattedPageResult(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $repository->expects(self::once())
            ->method('pageForAdmin')
            ->with(['status' => 'pending_review'], 1, 10)
            ->willReturn([
                'list' => [
                    [
                        'id' => 88,
                        'after_sale_no' => 'AS202603160088',
                        'order_id' => 10,
                        'order_item_id' => 20,
                        'member_id' => 1,
                        'type' => 'refund_only',
                        'status' => 'pending_review',
                        'refund_status' => 'pending',
                        'return_status' => 'not_required',
                        'apply_amount' => 18800,
                        'refund_amount' => 18800,
                        'quantity' => 1,
                        'reason' => 'size issue',
                        'description' => 'apply refund',
                        'images' => ['https://img.example/1.png'],
                        'created_at' => '2026-03-16 10:00:00',
                        'updated_at' => '2026-03-16 10:00:00',
                        'order' => ['order_no' => 'O202603160001'],
                        'order_item' => [
                            'product_id' => 11,
                            'sku_id' => 22,
                            'product_name' => 'Test product',
                            'sku_name' => 'Default sku',
                            'product_image' => 'https://img.example/product.png',
                        ],
                    ],
                ],
                'total' => 1,
            ]);

        $service = new AppAfterSaleQueryService($repository);
        $result = $service->page(['status' => 'pending_review'], 1, 10);

        self::assertSame(1, $result['total']);
        self::assertSame('O202603160001', $result['list'][0]['order_no']);
        self::assertSame('Test product', $result['list'][0]['product']['productName']);
    }

    public function testQueryServiceReturnsDetail(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $model = $this->makeAfterSaleModel();

        $repository->expects(self::once())
            ->method('findDetailById')
            ->with(88)
            ->willReturn($model);

        $service = new AppAfterSaleQueryService($repository);
        $detail = $service->detail(88);

        self::assertSame('AS202603160088', $detail['after_sale_no']);
        self::assertSame('Test product', $detail['product']['productName']);
    }

    public function testCommandServiceApproveUpdatesEntityAndReturnsDetail(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $input = $this->createStub(AfterSaleReviewInput::class);

        $input->method('getId')->willReturn(88);
        $input->method('getApprovedRefundAmount')->willReturn(15000);

        $repository->expects(self::once())
            ->method('findById')
            ->with(88)
            ->willReturn($this->makeAfterSaleModel());

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getId() === 88
                    && $entity->getRefundAmount() === 15000
                    && $entity->getStatus() === 'waiting_refund';
            }))
            ->willReturn(true);

        $queryService->expects(self::once())
            ->method('detail')
            ->with(88)
            ->willReturn(['id' => 88, 'status' => 'waiting_refund']);

        $service = new AppAfterSaleCommandService($repository, $queryService);
        $result = $service->approve($input);

        self::assertSame('waiting_refund', $result['status']);
    }

    public function testCommandServiceRejectUpdatesEntityAndReturnsDetail(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $input = $this->createStub(AfterSaleReviewInput::class);

        $input->method('getId')->willReturn(88);
        $input->method('getApprovedRefundAmount')->willReturn(null);

        $repository->expects(self::once())
            ->method('findById')
            ->with(88)
            ->willReturn($this->makeAfterSaleModel());

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getId() === 88
                    && $entity->getStatus() === 'closed';
            }))
            ->willReturn(true);

        $queryService->expects(self::once())
            ->method('detail')
            ->with(88)
            ->willReturn(['id' => 88, 'status' => 'closed']);

        $service = new AppAfterSaleCommandService($repository, $queryService);
        $result = $service->reject($input);

        self::assertSame('closed', $result['status']);
    }

    public function testCommandServiceReceiveUpdatesReturnRefundToWaitingRefund(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $input = $this->createStub(AfterSaleActionInput::class);

        $input->method('getId')->willReturn(89);

        $repository->expects(self::once())
            ->method('findById')
            ->with(89)
            ->willReturn($this->makeAfterSaleModel(id: 89, type: 'return_refund', status: 'waiting_seller_receive', returnStatus: 'buyer_shipped'));

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getId() === 89
                    && $entity->getStatus() === 'waiting_refund'
                    && $entity->getReturnStatus() === 'seller_received';
            }))
            ->willReturn(true);

        $queryService->expects(self::once())
            ->method('detail')
            ->with(89)
            ->willReturn(['id' => 89, 'status' => 'waiting_refund']);

        $service = new AppAfterSaleCommandService($repository, $queryService);
        $result = $service->receive($input);

        self::assertSame('waiting_refund', $result['status']);
    }

    public function testCommandServiceRefundCompletesAfterSale(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $input = $this->createStub(AfterSaleActionInput::class);

        $input->method('getId')->willReturn(90);

        $repository->expects(self::once())
            ->method('findById')
            ->with(90)
            ->willReturn($this->makeAfterSaleModel(id: 90, status: 'waiting_refund'));

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getId() === 90
                    && $entity->getStatus() === 'completed'
                    && $entity->getRefundStatus() === 'refunded';
            }))
            ->willReturn(true);

        $queryService->expects(self::once())
            ->method('detail')
            ->with(90)
            ->willReturn(['id' => 90, 'status' => 'completed']);

        $service = new AppAfterSaleCommandService($repository, $queryService);
        $result = $service->refund($input);

        self::assertSame('completed', $result['status']);
    }

    public function testCommandServiceReshipUpdatesExchangeAfterSale(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $input = $this->createStub(AfterSaleReshipInput::class);

        $input->method('getId')->willReturn(91);
        $input->method('getLogisticsCompany')->willReturn('SF');
        $input->method('getLogisticsNo')->willReturn('SF123456');

        $repository->expects(self::once())
            ->method('findById')
            ->with(91)
            ->willReturn($this->makeAfterSaleModel(id: 91, type: 'exchange', status: 'waiting_reship', returnStatus: 'seller_received'));

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getId() === 91
                    && $entity->getStatus() === 'reshipped'
                    && $entity->getReshipLogisticsCompany() === 'SF'
                    && $entity->getReshipLogisticsNo() === 'SF123456';
            }))
            ->willReturn(true);

        $queryService->expects(self::once())
            ->method('detail')
            ->with(91)
            ->willReturn(['id' => 91, 'status' => 'reshipped']);

        $service = new AppAfterSaleCommandService($repository, $queryService);
        $result = $service->reship($input);

        self::assertSame('reshipped', $result['status']);
    }



    public function testCommandServiceCompleteExchangeMarksAfterSaleCompleted(): void
    {
        $repository = $this->createMock(AfterSaleRepository::class);
        $queryService = $this->createMock(AppAfterSaleQueryService::class);
        $input = $this->createStub(AfterSaleActionInput::class);

        $input->method('getId')->willReturn(92);

        $repository->expects(self::once())
            ->method('findById')
            ->with(92)
            ->willReturn($this->makeAfterSaleModel(id: 92, type: 'exchange', status: 'reshipped', returnStatus: 'seller_reshipped'));

        $repository->expects(self::once())
            ->method('updateFromEntity')
            ->with(self::callback(static function ($entity): bool {
                return $entity->getId() === 92
                    && $entity->getStatus() === 'completed'
                    && $entity->getReturnStatus() === 'buyer_received';
            }))
            ->willReturn(true);

        $queryService->expects(self::once())
            ->method('detail')
            ->with(92)
            ->willReturn(['id' => 92, 'status' => 'completed']);

        $service = new AppAfterSaleCommandService($repository, $queryService);
        $result = $service->completeExchange($input);

        self::assertSame('completed', $result['status']);
    }

    private function makeAfterSaleModel(
        int $id = 88,
        string $type = 'refund_only',
        string $status = 'pending_review',
        string $refundStatus = 'pending',
        string $returnStatus = 'not_required',
    ): AfterSale {
        $order = new class extends Order {
            public function __construct() {}
        };
        $order->setRawAttributes([
            'id' => 10,
            'order_no' => 'O202603160001',
        ], true);

        $orderItem = new class extends OrderItem {
            public function __construct() {}
        };
        $orderItem->setRawAttributes([
            'id' => 20,
            'product_id' => 11,
            'sku_id' => 22,
            'product_name' => 'Test product',
            'sku_name' => 'Default sku',
            'product_image' => 'https://img.example/product.png',
        ], true);

        $model = new class extends AfterSale {
            public function __construct() {}
        };
        $model->setRawAttributes([
            'id' => $id,
            'after_sale_no' => 'AS202603160088',
            'order_id' => 10,
            'order_item_id' => 20,
            'member_id' => 1,
            'type' => $type,
            'status' => $status,
            'refund_status' => $refundStatus,
            'return_status' => $returnStatus,
            'apply_amount' => 18800,
            'refund_amount' => 18800,
            'quantity' => 1,
            'reason' => 'size issue',
            'description' => 'apply refund',
            'images' => '["https://img.example/1.png"]',
            'buyer_return_logistics_company' => null,
            'buyer_return_logistics_no' => null,
            'reship_logistics_company' => null,
            'reship_logistics_no' => null,
            'created_at' => '2026-03-16 10:00:00',
            'updated_at' => '2026-03-16 10:00:00',
        ], true);
        $model->setRelation('order', $order);
        $model->setRelation('orderItem', $orderItem);

        return $model;
    }
}
