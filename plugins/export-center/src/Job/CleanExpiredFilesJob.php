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

namespace Plugin\ExportCenter\Job;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\ExportCenter\Enum\ExportStatus;
use Plugin\ExportCenter\Model\ExportTask;
use Plugin\ExportCenter\Service\ExportFileService;
use Psr\Log\LoggerInterface;

class CleanExpiredFilesJob extends Job
{
    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $fileService = $container->get(ExportFileService::class);
        $logger = $container->get(LoggerInterface::class);

        $logger->info('开始执行过期文件清理任务');

        $expiredTasks = ExportTask::query()
            ->where('expired_at', '<', Carbon::now())
            ->where('status', '!=', ExportStatus::EXPIRED->value)
            ->get();

        $totalCount = $expiredTasks->count();
        $deletedCount = 0;
        $failedCount = 0;

        foreach ($expiredTasks as $task) {
            try {
                if ($task->file_path) {
                    $fileService->deleteFile($task->file_path);
                }

                $task->update(['status' => ExportStatus::EXPIRED->value]);

                ++$deletedCount;
            } catch (\Throwable $e) {
                ++$failedCount;
                $logger->error('清理过期任务失败', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $logger->info('过期文件清理任务完成', [
            'total' => $totalCount,
            'deleted' => $deletedCount,
            'failed' => $failedCount,
        ]);
    }
}
