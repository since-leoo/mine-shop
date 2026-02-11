<?php

declare(strict_types=1);

namespace App\Application\Admin\Dashboard;

use App\Domain\Infrastructure\Statistics\Service\DomainStatisticsService;
use Carbon\Carbon;

/**
 * 仪表盘查询应用服务.
 */
final class AppDashboardQueryService
{
    public function __construct(
        private readonly DomainStatisticsService $statisticsService,
    ) {}

    /**
     * 商城首页欢迎页（实时 + 近期统计）.
     */
    public function welcome(): array
    {
        return $this->statisticsService->welcome();
    }

    /**
     * 数据分析页.
     */
    public function analysis(array $params): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($params);
        return $this->statisticsService->analysis($startDate, $endDate);
    }

    /**
     * 多维度报表页.
     */
    public function report(array $params): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($params);
        return $this->statisticsService->report($startDate, $endDate);
    }

    /**
     * 解析日期范围参数，默认近30天.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(array $params): array
    {
        $endDate = ! empty($params['end_date'])
            ? Carbon::parse($params['end_date'])->toDateString()
            : Carbon::yesterday()->toDateString();

        $startDate = ! empty($params['start_date'])
            ? Carbon::parse($params['start_date'])->toDateString()
            : Carbon::parse($endDate)->subDays(29)->toDateString();

        return [$startDate, $endDate];
    }
}
