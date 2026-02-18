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

namespace App\Infrastructure\Crontab;

use App\Domain\Infrastructure\Statistics\Service\DomainStatisticsService;
use Carbon\Carbon;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

/**
 * 统计数据聚合定时任务.
 *
 * 每小时执行一次，聚合昨日和今日的统计数据到各 stat_daily_* 表。
 * 昨日数据保证完整性，今日数据提供近实时预览。
 */
#[Crontab(
    name: 'statistics-aggregate',
    rule: '5 * * * *',
    callback: 'execute',
    memo: '统计数据聚合（每小时）',
    enable: true
)]
class StatisticsAggregateCrontab
{
    public function __construct(
        private readonly DomainStatisticsService $statisticsService,
        private readonly LoggerInterface $logger,
    ) {}

    public function execute(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();
        $today = Carbon::today()->toDateString();

        try {
            // 聚合昨日数据（确保完整）
            $this->statisticsService->aggregateAll($yesterday);
            $this->logger->info('[StatisticsAggregate] 昨日数据聚合完成', ['date' => $yesterday]);

            // 聚合今日数据（近实时预览）
            $this->statisticsService->aggregateAll($today);
            $this->logger->info('[StatisticsAggregate] 今日数据聚合完成', ['date' => $today]);
        } catch (\Throwable $e) {
            $this->logger->error('[StatisticsAggregate] 聚合失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
