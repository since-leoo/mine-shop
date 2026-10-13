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

namespace Plugin\Analytics;

use App\Domain\Infrastructure\Plugin\Contract\AnalyticsQueryInterface;
use Plugin\Analytics\Domain\Infrastructure\Statistics\Service\DomainStatisticsService;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => ['scan' => ['paths' => [__DIR__]]],
            'plugin_capabilities' => [
                AnalyticsQueryInterface::class => DomainStatisticsService::class,
            ],
        ];
    }
}
