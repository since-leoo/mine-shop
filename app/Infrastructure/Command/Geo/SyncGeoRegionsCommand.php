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

namespace App\Infrastructure\Command\Geo;

use App\Domain\Infrastructure\Geo\Service\GeoRegionSyncService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
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
            'parallel_workers' => (int) $this->input->getOption('parallel-workers'),
            'batch_size' => (int) $this->input->getOption('batch-size'),
            'chunk_size' => (int) $this->input->getOption('chunk-size'),
        ];

        $this->info(\sprintf('Start syncing geo regions (source=%s, version=%s)', $options['source'], $options['version']));

        try {
            $summary = $this->syncService->sync($options);
        } catch (\Throwable $throwable) {
            $this->error('Sync failed: ' . $throwable->getMessage());
            return self::FAILURE;
        }

        if (! empty($summary['dry_run'])) {
            $this->line(\sprintf('DRY-RUN: would write %d records (version=%s)', $summary['records'] ?? 0, $summary['version'] ?? $options['version']));
        } else {
            $this->info(\sprintf('Sync completed: wrote %d records (version=%s)', $summary['records'] ?? 0, $summary['version'] ?? $options['version']));
        }

        return self::SUCCESS;
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Sync geo regions data into geo_regions');
        $this->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Data source key', 'areacity');
        $this->addOption('url', null, InputOption::VALUE_OPTIONAL, 'Custom source URL');
        $this->addOption('released-at', null, InputOption::VALUE_OPTIONAL, 'Upstream release date (YYYY-MM-DD)');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite same version if exists');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse only, do not write DB');
        $this->addOption('parallel-workers', null, InputOption::VALUE_OPTIONAL, 'Coroutine workers for parallel insert', 4);
        $this->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Records per flush', 1000);
        $this->addOption('chunk-size', null, InputOption::VALUE_OPTIONAL, 'Records per insert chunk', 200);
    }
}

