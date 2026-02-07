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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Event\OrderCreatedEvent;
use App\Domain\Trade\Order\Listener\OrderCreatedListener;
use Hyperf\Logger\LoggerFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * 单元测试：OrderCreatedListener 日志记录.
 *
 * 验证 listener 处理事件时记录正确的日志字段（order_no, member_id, pay_amount）。
 *
 * **Validates: Requirements 12.2**
 *
 * @internal
 * @coversNothing
 */
final class OrderCreatedListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Test that listen() returns [OrderCreatedEvent::class].
     */
    public function testListenReturnsOrderCreatedEventClass(): void
    {
        $loggerFactory = \Mockery::mock(LoggerFactory::class);
        $listener = new OrderCreatedListener($loggerFactory);

        self::assertSame([OrderCreatedEvent::class], $listener->listen());
    }

    /**
     * Test that process() logs the correct fields when receiving an OrderCreatedEvent.
     *
     * Validates: Requirement 12.2 - WHEN OrderCreatedEvent 被触发,
     * THE OrderCreatedListener SHALL 记录订单创建日志（订单号、会员ID、订单金额）
     */
    public function testProcessLogsCorrectFieldsForOrderCreatedEvent(): void
    {
        $orderNo = 'ORD202506280001';
        $memberId = 42;
        $payAmount = 19999;

        // Use a real OrderEntity instance (final class cannot be mocked)
        $orderEntity = new OrderEntity();
        $orderEntity->setOrderNo($orderNo);
        $orderEntity->setMemberId($memberId);
        $orderEntity->setPayAmount($payAmount);

        // Mock Logger
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')
            ->once()
            ->with('订单创建', [
                'order_no' => $orderNo,
                'member_id' => $memberId,
                'pay_amount' => $payAmount,
            ]);

        // Mock LoggerFactory
        $loggerFactory = \Mockery::mock(LoggerFactory::class);
        $loggerFactory->shouldReceive('get')
            ->once()
            ->with('order')
            ->andReturn($logger);

        $listener = new OrderCreatedListener($loggerFactory);
        $event = new OrderCreatedEvent($orderEntity);

        $listener->process($event);
    }

    /**
     * Test that process() does nothing when receiving a non-OrderCreatedEvent object.
     */
    public function testProcessDoesNothingForNonOrderCreatedEvent(): void
    {
        // LoggerFactory should never be called
        $loggerFactory = \Mockery::mock(LoggerFactory::class);
        $loggerFactory->shouldNotReceive('get');

        $listener = new OrderCreatedListener($loggerFactory);

        // Pass a generic object that is not an OrderCreatedEvent
        $nonEvent = new \stdClass();
        $listener->process($nonEvent);
    }
}
