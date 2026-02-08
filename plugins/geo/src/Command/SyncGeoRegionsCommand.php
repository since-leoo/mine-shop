<?php

declare(strict_types=1);

namespace Plugin\Since\Geo\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Plugin\Since\Geo\Service\GeoRegionSyncService;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class SyncGeoRegionsCommand extends HyperfCommand
{
    public function __construct(
        protected ContainerInterface $container,
        private readonly GeoRegionSyncService $syncService,
    ) {
        parent::__construct('mall:sync-regions');
    }

    public function handle()
    {
        $options = [
            'source' => (string) $this->input->getOption('source'),
            'url' => $this->input->getOption('url') ?: null,
            'version' => $this->input->getOption('version') ?: date('Y-m-d'),
            'released_at' => $this->input->getOption('released-at') ?: null,
            'force' => (bool) $this->input->getOption('force'),
            'dry_run' => (bool) $this->input->getOption('dry-run'),
        ];

        $this->info(\sprintf('开始同步行政区划（source=%s, version=%s）', $options['source'], $options['version']));

        try {
            $summary = $this->syncService->sync($options);
        } catch (\Throwable $throwable) {
            $this->error('同步失败：' . $throwable->getMessage());
            return self::FAILURE;
        }

        if (! empty($summary['dry_run'])) {
            $this->line(\sprintf('DRY-RUN：预计写入 %d 条记录（version=%s）', $summary['records'] ?? 0, $summary['version'] ?? $options['version']));
        } else {
            $this->info(\sprintf('同步完成，写入 %d 条记录（version=%s）', $summary['records'] ?? 0, $summary['version'] ?? $options['version']));
        }

        return self::SUCCESS;
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription('同步四级行政区划数据到 geo_regions 地址库');
        $this->addOption('source', null, InputOption::VALUE_OPTIONAL, '数据来源标识', 'modood');
        $this->addOption('url', null, InputOption::VALUE_OPTIONAL, '自定义数据源地址');
        $this->addOption('released-at', null, InputOption::VALUE_OPTIONAL, '上游发布时间，YYYY-MM-DD');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, '存在相同版本时覆盖');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, '仅解析不写入数据库');
    }
}
