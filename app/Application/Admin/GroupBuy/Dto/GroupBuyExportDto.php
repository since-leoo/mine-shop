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

namespace App\Application\Admin\GroupBuy\Dto;

use App\Domain\Trade\GroupBuy\Repository\GroupBuyRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '团购活动',
    description: '团购活动导出',
    dataProvider: [GroupBuyRepository::class, 'getExportData'],
)]
class GroupBuyExportDto
{
    #[ExportColumn(title: 'ID', order: 1, type: 'int', width: 8)]
    public int $id;

    #[ExportColumn(title: '活动标题', order: 2, width: 24)]
    public string $title;

    #[ExportColumn(title: '关联商品', order: 3, width: 20, field: 'product.name', default: '-')]
    public string $product_name;

    #[ExportColumn(title: '原价(元)', order: 4, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $original_price;

    #[ExportColumn(title: '团购价(元)', order: 5, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $group_price;

    #[ExportColumn(title: '最少人数', order: 6, type: 'int', width: 10)]
    public int $min_people;

    #[ExportColumn(title: '最多人数', order: 7, type: 'int', width: 10)]
    public int $max_people;

    #[ExportColumn(title: '开始时间', order: 8, width: 20, default: '-')]
    public string $start_time;

    #[ExportColumn(title: '结束时间', order: 9, width: 20, default: '-')]
    public string $end_time;

    #[ExportColumn(title: '状态', order: 10, width: 10, enum: ['pending' => '待开始', 'active' => '进行中', 'ended' => '已结束', 'cancelled' => '已取消', 'sold_out' => '已售罄'])]
    public string $status;

    #[ExportColumn(title: '总库存', order: 11, type: 'int', width: 10)]
    public int $total_quantity;

    #[ExportColumn(title: '已售', order: 12, type: 'int', width: 10)]
    public int $sold_quantity;

    #[ExportColumn(title: '开团数', order: 13, type: 'int', width: 10)]
    public int $group_count;

    #[ExportColumn(title: '成团数', order: 14, type: 'int', width: 10)]
    public int $success_group_count;

    #[ExportColumn(title: '是否启用', order: 15, width: 10, type: 'boolean')]
    public string $is_enabled;

    #[ExportColumn(title: '创建时间', order: 16, width: 20)]
    public string $created_at;
}
