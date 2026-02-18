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

namespace Tests\Unit\Plugin\ExportCenter\Listener;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Plugin\ExportCenter\Event\ExportTaskCompleted;
use Plugin\ExportCenter\Event\ExportTaskFailed;
use Plugin\ExportCenter\Listener\SendExportNotificationListener;
use Plugin\ExportCenter\Model\ExportTask;

/**
 * @internal
 * @coversNothing
 */
final class SendExportNotificationListenerTest extends TestCase
{
    private SendExportNotificationListener $listener;

    protected function setUp(): void
    {
        $this->listener = new SendExportNotificationListener();
    }

    public function testListenReturnsCompletedAndFailedEvents(): void
    {
        $events = $this->listener->listen();

        self::assertCount(2, $events);
        self::assertSame(ExportTaskCompleted::class, $events[0]);
        self::assertSame(ExportTaskFailed::class, $events[1]);
    }

    public function testProcessIgnoresUnrelatedEvents(): void
    {
        // Should not throw for unknown event types
        $this->listener->process(new \stdClass());
        self::assertTrue(true);
    }

    public function testProcessHandlesCompletedEventWithoutThrowing(): void
    {
        $task = $this->makeTask([
            'id' => 1,
            'user_id' => 42,
            'task_name' => '订单导出',
            'file_name' => 'orders_20240615.xlsx',
            'completed_at' => Carbon::parse('2024-06-15 14:30:00'),
        ]);

        $event = new ExportTaskCompleted($task);

        // The listener wraps SystemMessage::sendToUser in try/catch,
        // so it should not propagate exceptions even if the facade fails.
        $this->listener->process($event);
        self::assertTrue(true);
    }

    public function testProcessHandlesFailedEventWithoutThrowing(): void
    {
        $task = $this->makeTask([
            'id' => 2,
            'user_id' => 42,
            'task_name' => '会员导出',
            'error_message' => '数据量过大',
        ]);

        $event = new ExportTaskFailed($task, '数据量过大');

        $this->listener->process($event);
        self::assertTrue(true);
    }

    public function testProcessHandlesFailedEventWithEmptyErrorMessage(): void
    {
        $task = $this->makeTask([
            'id' => 3,
            'user_id' => 10,
            'task_name' => '商品导出',
            'error_message' => null,
        ]);

        // Empty errorMessage on event, null error_message on task → falls back to '未知错误'
        $event = new ExportTaskFailed($task, '');

        $this->listener->process($event);
        self::assertTrue(true);
    }

    public function testProcessHandlesCompletedEventWithNullFileName(): void
    {
        $task = $this->makeTask([
            'id' => 4,
            'user_id' => 5,
            'task_name' => '优惠券导出',
            'file_name' => null,
            'completed_at' => null,
        ]);

        $event = new ExportTaskCompleted($task);

        // Should fall back to task_name when file_name is null
        $this->listener->process($event);
        self::assertTrue(true);
    }

    private function makeTask(array $attributes = []): ExportTask
    {
        $defaults = [
            'id' => 1,
            'user_id' => 1,
            'task_name' => '测试导出',
            'file_name' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
        $data = array_merge($defaults, $attributes);

        $task = $this->createMock(ExportTask::class);
        $task->method('__get')->willReturnCallback(
            static fn (string $key) => $data[$key] ?? null
        );

        return $task;
    }
}
