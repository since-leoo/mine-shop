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
use App\Domain\Infrastructure\Plugin\Contract\PluginPermissionRegistrarInterface;
use Hyperf\Context\ApplicationContext;
use Hyperf\Database\Seeders\Seeder;

class supply_chain_permission_seeder_20261016 extends Seeder
{
    public function run(): void
    {
        ApplicationContext::getContainer()->get(PluginPermissionRegistrarInterface::class)->register('supply-chain', [
            'supply-chain:connections/read' => '查看供应链连接', 'supply-chain:connections/update' => '配置供应链连接',
            'supply-chain:connections/test' => '测试供应链连接', 'supply-chain:catalog/read' => '供应链选品查询',
            'supply-chain:catalog/import' => '导入供应链商品', 'supply-chain:products/read' => '查看供应链映射',
            'supply-chain:products/sync' => '同步供应链商品', 'supply-chain:purchases/read' => '查看供应链采购',
            'supply-chain:purchases/query' => '查询上游采购结果', 'supply-chain:after-sales/read' => '查看采购售后',
            'supply-chain:after-sales/submit' => '提交采购售后', 'supply-chain:after-sales/query' => '查询采购售后',
            'supply-chain:tasks/read' => '查看供应链任务', 'supply-chain:tasks/retry' => '重试供应链任务',
            'supply-chain:logs/read' => '查看供应链接口日志', 'supply-chain:logs/params' => '查看脱敏业务参数',
        ]);
    }
}
