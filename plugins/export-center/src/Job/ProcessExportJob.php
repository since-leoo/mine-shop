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

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\ExportCenter\Service\ExportService;
use Psr\Log\LoggerInterface;

class ProcessExportJob extends Job
{
    /**
     * 不使用队列自动重试，由业务层控制重试逻辑.
     */
    public int $maxAttempts = 1;

    public function __construct(
        public int $taskId
    ) {}

    public function handle(): void
    {
        $container = ApplicationContext::getContainer();
        $exportService = $container->get(ExportService::class);
        $logger = $container->get(LoggerInterface::class);

        try {
            $logger->info('开始处理导出任务', ['task_id' => $this->taskId]);

            $exportService->processExportTask($this->taskId);

            $logger->info('导出任务处理完成', ['task_id' => $this->taskId]);
        } catch (\Throwable $e) {
            $logger->error('导出任务处理失败', [
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 直接标记失败，不再 throw（避免队列重试时状态已非 pending）
            $exportService->markTaskFailed($this->taskId, $e->getMessage());
        }
    }
}
