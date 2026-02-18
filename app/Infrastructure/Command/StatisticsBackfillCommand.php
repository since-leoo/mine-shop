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

namespace App\Infrastructure\Command;

use App\Domain\Infrastructure\Statistics\Service\DomainStatisticsService;
use Carbon\Carbon;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * 统计数据回填命令.
 *
 * 用于首次部署或数据修复时，批量聚合历史统计数据。
 *
 * 用法：
 *   php bin/hyperf.php statistics:backfill              # 回填最近30天
 *   php bin/hyperf.php statistics:backfill --days=90     # 回填最近90天
 *   php bin/hyperf.php statistics:backfill 2026-01-01 2026-02-10  # 指定日期范围
 */
#[Command]
class StatisticsBackfillCommand extends HyperfCommand
{
    protected ?string $name = 'statistics:backfill';

    protected string $description = '回填统计数据到 stat_daily_* 表';

    public function __construct(
        private readonly ContainerInterface $container,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        parent::configure();
        $this->addArgument('start_date', InputArgument::OPTIONAL, '开始日期 (Y-m-d)');
        $this->addArgument('end_date', InputArgument::OPTIONAL, '结束日期 (Y-m-d)');
        $this->addOption('days', 'd', InputOption::VALUE_OPTIONAL, '回填最近N天', '30');
    }

    public function handle(): void
    {
        $service = $this->container->get(DomainStatisticsService::class);

        $startDate = $this->input->getArgument('start_date');
        $endDate = $this->input->getArgument('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } else {
            $days = (int) $this->input->getOption('days');
            $end = Carbon::yesterday();
            $start = $end->copy()->subDays($days - 1);
        }

        $this->info("开始回填统计数据: {$start->toDateString()} ~ {$end->toDateString()}");

        $current = $start->copy();
        $total = 0;

        while ($current->lte($end)) {
            $date = $current->toDateString();
            try {
                $service->aggregateAll($date);
                $this->line("  ✓ {$date}");
                ++$total;
            } catch (\Throwable $e) {
                $this->error("  ✗ {$date}: {$e->getMessage()}");
            }
            $current->addDay();
        }

        $this->info("回填完成，共处理 {$total} 天数据。");
    }
}
