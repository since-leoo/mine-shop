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

namespace App\Infrastructure\Command\Product;

use App\Infrastructure\Service\Product\ProductMockDataService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

#[Command]
class MockProductDataCommand extends HyperfCommand
{
    public function __construct(
        ContainerInterface $container,
        private readonly ProductMockDataService $service,
    ) {
        parent::__construct('mall:mock-products');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('快速生成商城商品/品牌/分类的 mock 数据');
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, '限制生成的商品数量', null);
        $this->addOption('force', 'f', InputOption::VALUE_NONE, '生成前清空相关数据表');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, '仅输出预期写入数量，不真正写库');
    }

    public function handle()
    {
        $limit = $this->input->getOption('limit');
        $force = (bool) $this->input->getOption('force');
        $dryRun = (bool) $this->input->getOption('dry-run');

        if ($dryRun && $force) {
            $this->warn('dry-run 模式下会忽略 --force 选项。');
        }

        $summary = $this->service->seed([
            'limit' => $limit !== null ? (int) $limit : null,
            'force' => $dryRun ? false : $force,
            'dry_run' => $dryRun,
        ]);

        $this->table(
            ['类型', '数量'],
            [
                ['分类', $summary['categories'] ?? 0],
                ['品牌', $summary['brands'] ?? 0],
                ['商品', $summary['products'] ?? 0],
                ['SKU', $summary['skus'] ?? 0],
                ['属性', $summary['attributes'] ?? 0],
                ['图片', $summary['gallery'] ?? 0],
            ],
        );

        if (! empty($summary['missing_categories'])) {
            $this->warn('以下分类在模板中定义但尚未写入：' . implode(', ', $summary['missing_categories']));
        }

        if ($summary['dry_run'] ?? false) {
            $this->info('Dry-run 完成，未对数据库做任何写入。');
        } else {
            $this->info('商品 mock 数据已生成完毕，可以通过 mall:mock-products --limit=N 重复执行。');
        }

        return self::SUCCESS;
    }
}
