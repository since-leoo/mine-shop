<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Api\Logistics;

use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;
use App\Application\Api\Order\AppApiOrderQueryService;
use PHPUnit\Framework\TestCase;
use Plugin\Express\Contract\LogisticsTrackingInterface;
use Plugin\Express\ValueObject\TrackingResult;
use Plugin\Express\ValueObject\TrackingTrace;

/**
 * @internal
 * @coversNothing
 */
final class AppApiLogisticsTrackingQueryServiceTest extends TestCase
{
    public function testTrackOrderReturnsNormalizedTrackingData(): void
    {
        $orderQueryService = $this->createMock(AppApiOrderQueryService::class);
        $afterSaleQueryService = $this->createMock(AppApiAfterSaleQueryService::class);
        $tracking = $this->createMock(LogisticsTrackingInterface::class);

        $order = new class() {
            public array $packages;

            public function __construct()
            {
                $this->packages = [
                    (object) [
                        'express_company' => 'shunfeng',
                        'express_no' => 'SF123456789',
                    ],
                ];
            }
        };

        $orderQueryService->method('getOrderDetail')->with(1, 'ORDER-001')->willReturn($order);
        $tracking->method('track')->with('shunfeng', 'SF123456789')->willReturn($this->makeTrackingResult());

        $service = new \App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService(
            $orderQueryService,
            $afterSaleQueryService,
            $tracking
        );

        self::assertSame('signed', $service->trackOrder(1, 'ORDER-001')['status']);
    }

    public function testTrackAfterSaleReturnUsesBuyerReturnShipment(): void
    {
        $orderQueryService = $this->createMock(AppApiOrderQueryService::class);
        $afterSaleQueryService = $this->createMock(AppApiAfterSaleQueryService::class);
        $tracking = $this->createMock(LogisticsTrackingInterface::class);

        $afterSale = (object) [
            'buyer_return_logistics_company' => 'yuantong',
            'buyer_return_logistics_no' => 'YT123456789',
            'reship_logistics_company' => null,
            'reship_logistics_no' => null,
        ];

        $afterSaleQueryService->method('detail')->with(1, 99)->willReturn($afterSale);
        $tracking->method('track')->with('yuantong', 'YT123456789')->willReturn($this->makeTrackingResult());

        $service = new \App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService(
            $orderQueryService,
            $afterSaleQueryService,
            $tracking
        );

        self::assertSame('signed', $service->trackAfterSaleReturn(1, 99)['status']);
    }

    public function testTrackAfterSaleReshipUsesReshipShipment(): void
    {
        $orderQueryService = $this->createMock(AppApiOrderQueryService::class);
        $afterSaleQueryService = $this->createMock(AppApiAfterSaleQueryService::class);
        $tracking = $this->createMock(LogisticsTrackingInterface::class);

        $afterSale = (object) [
            'buyer_return_logistics_company' => null,
            'buyer_return_logistics_no' => null,
            'reship_logistics_company' => 'ems',
            'reship_logistics_no' => 'EMS123456789',
        ];

        $afterSaleQueryService->method('detail')->with(1, 100)->willReturn($afterSale);
        $tracking->method('track')->with('ems', 'EMS123456789')->willReturn($this->makeTrackingResult());

        $service = new \App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService(
            $orderQueryService,
            $afterSaleQueryService,
            $tracking
        );

        self::assertSame('signed', $service->trackAfterSaleReship(1, 100)['status']);
    }

    public function testTrackOrderThrowsWhenShipmentDataIsMissing(): void
    {
        $orderQueryService = $this->createMock(AppApiOrderQueryService::class);
        $afterSaleQueryService = $this->createMock(AppApiAfterSaleQueryService::class);
        $tracking = $this->createMock(LogisticsTrackingInterface::class);

        $order = new class() {
            public array $packages = [];
        };

        $orderQueryService->method('getOrderDetail')->with(1, 'ORDER-EMPTY')->willReturn($order);

        $service = new \App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService(
            $orderQueryService,
            $afterSaleQueryService,
            $tracking
        );

        $this->expectException(\RuntimeException::class);
        $service->trackOrder(1, 'ORDER-EMPTY');
    }

    private function makeTrackingResult(): TrackingResult
    {
        return new TrackingResult(
            status: 'signed',
            companyCode: 'mock',
            companyName: 'Mock Express',
            trackingNo: 'MOCK123',
            traces: [
                new TrackingTrace(
                    time: '2026-04-10 10:00:00',
                    context: '已签收',
                    location: '上海',
                    status: 'signed'
                ),
            ],
            raw: ['mock' => true]
        );
    }
}
