<?php

declare(strict_types=1);

namespace Tests\Unit\Interface\Api\Controller\V1;

use App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService;
use App\Application\Api\Order\AppApiOrderCommandService;
use App\Application\Api\Order\AppApiOrderQueryService;
use App\Application\Api\Payment\AppApiOrderPaymentService;
use App\Interface\Api\Controller\V1\OrderController;
use App\Interface\Api\Transformer\OrderCheckoutTransformer;
use App\Interface\Api\Transformer\OrderTransformer;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderControllerLogisticsTest extends TestCase
{
    public function testLogisticsDelegatesToTrackingQueryService(): void
    {
        $trackingQueryService = $this->createMock(AppApiLogisticsTrackingQueryService::class);
        $trackingQueryService->expects(self::once())
            ->method('trackOrder')
            ->with(12, 'ORDER-001')
            ->willReturn(['status' => 'signed']);

        $controller = new OrderController(
            $this->createMock(AppApiOrderCommandService::class),
            $this->mockCurrentMember(12),
            $this->createMock(AppApiOrderPaymentService::class),
            $this->createMock(AppApiOrderQueryService::class),
            $this->createMock(OrderTransformer::class),
            $this->createMock(OrderCheckoutTransformer::class),
            $trackingQueryService,
        );

        $result = $controller->logistics('ORDER-001');

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
