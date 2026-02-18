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

namespace Plugin\ExportCenter\Crontab;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Crontab\Annotation\Crontab;
use Plugin\ExportCenter\Job\CleanExpiredFilesJob;
use Psr\Log\LoggerInterface;

/**
 * 过期导出文件清理定时任务.
 *
 * 每天凌晨 2:00 执行，将 CleanExpiredFilesJob 推送到异步队列，
 * 清理已过期的导出文件并更新任务状态为 EXPIRED。
 */
#[Crontab(
    name: 'export-clean-expired-files',
    rule: '0 2 * * *',
    callback: 'execute',
    memo: '清理过期导出文件（每天凌晨2点）',
    enable: true
)]
class CleanExpiredFilesCrontab
{
    public function __construct(
        private readonly DriverFactory $driverFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        try {
            $this->driverFactory->get('default')->push(new CleanExpiredFilesJob());
            $this->logger->info('[ExportCleanExpiredFiles] 清理任务已推送到队列');
        } catch (\Throwable $e) {
            $this->logger->error('[ExportCleanExpiredFiles] 推送清理任务失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
