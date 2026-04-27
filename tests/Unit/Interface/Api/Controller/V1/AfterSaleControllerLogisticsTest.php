<?php

declare(strict_types=1);

namespace Tests\Unit\Interface\Api\Controller\V1;

use App\Application\Api\AfterSale\AppApiAfterSaleCommandService;
use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;
use App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService;
use App\Interface\Api\Controller\V1\AfterSaleController;
use App\Interface\Api\Transformer\AfterSaleTransformer;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AfterSaleControllerLogisticsTest extends TestCase
{
    public function testReturnLogisticsDelegatesToTrackingQueryService(): void
    {
        $trackingQueryService = $this->createMock(AppApiLogisticsTrackingQueryService::class);
        $trackingQueryService->expects(self::once())
            ->method('trackAfterSaleReturn')
            ->with(8, 99)
            ->willReturn(['status' => 'signed']);

        $controller = new AfterSaleController(
            $this->createMock(AppApiAfterSaleQueryService::class),
            $this->createMock(AppApiAfterSaleCommandService::class),
            $this->createMock(AfterSaleTransformer::class),
            $this->mockCurrentMember(8),
            $this->createMock(RequestInterface::class),
            $trackingQueryService,
        );

        $result = $controller->returnLogistics(99);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame('signed', $result->data['status']);
    }

    public function testReshipLogisticsDelegatesToTrackingQueryService(): void
    {
        $trackingQueryService = $this->createMock(AppApiLogisticsTrackingQueryService::class);
        $trackingQueryService->expects(self::once())
            ->method('trackAfterSaleReship')
            ->with(8, 100)
            ->willReturn(['status' => 'signed']);

        $controller = new AfterSaleController(
            $this->createMock(AppApiAfterSaleQueryService::class),
            $this->createMock(AppApiAfterSaleCommandService::class),
            $this->createMock(AfterSaleTransformer::class),
            $this->mockCurrentMember(8),
            $this->createMock(RequestInterface::class),
            $trackingQueryService,
        );

        $result = $controller->reshipLogistics(100);

        self::assertInstanceOf(Result::class, $result);
        self::assertSame('signed', $result->data['status']);
    }

    private function mockCurrentMember(int $memberId): CurrentMember
    {
        $currentMember = $this->createMock(CurrentMember::class);
        $currentMember->method('id')->willReturn($memberId);
        return $currentMember;
    }
}
