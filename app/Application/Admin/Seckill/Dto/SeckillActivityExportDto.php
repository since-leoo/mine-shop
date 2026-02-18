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

namespace App\Application\Admin\Seckill\Dto;

use App\Domain\Trade\Seckill\Repository\SeckillActivityRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '秒杀活动',
    description: '秒杀活动导出',
    dataProvider: [SeckillActivityRepository::class, 'getExportData'],
)]
class SeckillActivityExportDto
{
    #[ExportColumn(title: 'ID', order: 1, type: 'int', width: 8)]
    public int $id;

    #[ExportColumn(title: '活动标题', order: 2, width: 24)]
    public string $title;

    #[ExportColumn(title: '描述', order: 3, width: 30, wrapText: true)]
    public string $description;

    #[ExportColumn(title: '状态', order: 4, width: 10, enum: ['active' => '进行中', 'pending' => '待开始', 'ended' => '已结束', 'sold_out' => '已售罄', 'cancelled' => '已取消'])]
    public string $status;

    #[ExportColumn(title: '是否启用', order: 5, width: 10, type: 'boolean')]
    public string $is_enabled;

    #[ExportColumn(title: '备注', order: 6, width: 20, wrapText: true)]
    public string $remark;

    #[ExportColumn(title: '创建时间', order: 7, width: 20)]
    public string $created_at;
}
