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

namespace App\Application\Admin\Coupon\Dto;

use App\Domain\Trade\Coupon\Repository\CouponRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '优惠券列表',
    description: '优惠券导出',
    dataProvider: [CouponRepository::class, 'getExportData'],
)]
class CouponExportDto
{
    #[ExportColumn(title: 'ID', order: 1, type: 'int', width: 8)]
    public int $id;

    #[ExportColumn(title: '名称', order: 2, width: 20)]
    public string $name;

    #[ExportColumn(title: '类型', order: 3, width: 10, field: 'type', enum: ['fixed' => '满减券', 'percent' => '折扣券'])]
    public string $type;

    #[ExportColumn(title: '面值/折扣', order: 4, width: 12, field: 'value_display')]
    public string $value;

    #[ExportColumn(title: '最低消费(元)', order: 5, type: 'float', width: 14, format: '#,##0.00', field: 'min_amount', divisor: 100)]
    public float $min_amount;

    #[ExportColumn(title: '总数量', order: 6, type: 'int', width: 10)]
    public int $total_quantity;

    #[ExportColumn(title: '已使用', order: 7, type: 'int', width: 10)]
    public int $used_quantity;

    #[ExportColumn(title: '每人限领', order: 8, type: 'int', width: 10)]
    public int $per_user_limit;

    #[ExportColumn(title: '开始时间', order: 9, width: 20, default: '-')]
    public string $start_time;

    #[ExportColumn(title: '结束时间', order: 10, width: 20, default: '-')]
    public string $end_time;

    #[ExportColumn(title: '状态', order: 11, width: 10, enum: ['active' => '启用', 'inactive' => '停用'])]
    public string $status;

    #[ExportColumn(title: '创建时间', order: 12, width: 20)]
    public string $created_at;
}
