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

namespace Plugin\ExportCenter\Listener;

use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;
use App\Domain\Infrastructure\SystemMessage\Facade\SystemMessage;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Plugin\ExportCenter\Event\ExportTaskCompleted;
use Plugin\ExportCenter\Event\ExportTaskFailed;

/**
 * 导出任务通知监听器.
 *
 * 监听导出任务完成和失败事件，通过系统消息通知用户。
 */
#[Listener]
class SendExportNotificationListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            ExportTaskCompleted::class,
            ExportTaskFailed::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof ExportTaskCompleted) {
            $this->handleCompleted($event);
        } elseif ($event instanceof ExportTaskFailed) {
            $this->handleFailed($event);
        }
    }

    /**
     * 处理导出任务完成事件.
     */
    protected function handleCompleted(ExportTaskCompleted $event): void
    {
        $task = $event->task;
        $completedAt = $task->completed_at?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s');
        $fileName = $task->file_name ?: $task->task_name;
        $downloadUrl = '/admin/export/tasks';

        $title = '导出完成通知';
        $content = "您的导出任务「{$fileName}」已完成。\n"
            . "完成时间：{$completedAt}\n"
            . "请前往下载中心查看并下载文件：{$downloadUrl}";

        try {
            SystemMessage::sendToUser(
                $task->user_id,
                $title,
                $content,
                MessageType::SYSTEM,
            );
        } catch (\Throwable $e) {
            logger()->error('发送导出完成通知失败', [
                'task_id' => $task->id,
                'user_id' => $task->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 处理导出任务失败事件.
     */
    protected function handleFailed(ExportTaskFailed $event): void
    {
        $task = $event->task;
        $errorMessage = $event->errorMessage ?: ($task->error_message ?: '未知错误');

        $title = '导出失败通知';
        $content = "您的导出任务「{$task->task_name}」处理失败。\n"
            . "失败原因：{$errorMessage}\n"
            . '您可以前往下载中心重试：/admin/export/tasks';

        try {
            SystemMessage::sendToUser(
                $task->user_id,
                $title,
                $content,
                MessageType::SYSTEM,
            );
        } catch (\Throwable $e) {
            logger()->error('发送导出失败通知失败', [
                'task_id' => $task->id,
                'user_id' => $task->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
