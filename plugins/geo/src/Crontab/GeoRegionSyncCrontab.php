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

namespace Plugin\Since\Geo\Crontab;

use Hyperf\Crontab\Annotation\Crontab;
use Plugin\Since\Geo\Service\GeoRegionSyncService;
use Psr\Log\LoggerInterface;

#[Crontab(
    name: 'geo-region-sync',
    rule: '30 3 * * 1',
    callback: 'execute',
    memo: '每周一凌晨自动同步四级行政区划',
    enable: true
)]
class GeoRegionSyncCrontab
{
    public function __construct(
        private readonly GeoRegionSyncService $syncService,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(): void
    {
        try {
            $summary = $this->syncService->sync();
            $this->logger->info(\sprintf(
                '[geo-region-sync] version=%s records=%d source=%s',
                $summary['version'] ?? 'unknown',
                $summary['records'] ?? 0,
                $summary['source'] ?? 'modood'
            ));
        } catch (\Throwable $throwable) {
            $this->logger->error('[geo-region-sync] 同步失败：' . $throwable->getMessage(), [
                'trace' => $throwable->getTraceAsString(),
            ]);
        }
    }
}
