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

use App\Domain\Trade\Seckill\Repository\SeckillOrderRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '秒杀订单',
    description: '秒杀订单导出',
    dataProvider: [SeckillOrderRepository::class, 'getExportData'],
)]
class SeckillOrderExportDto
{
    #[ExportColumn(title: 'ID', order: 1, type: 'int', width: 8)]
    public int $id;

    #[ExportColumn(title: '关联订单ID', order: 2, type: 'int', width: 12)]
    public int $order_id;

    #[ExportColumn(title: '活动名称', order: 3, width: 20, field: 'activity.title', default: '-')]
    public string $activity_title;

    #[ExportColumn(title: '会员昵称', order: 4, width: 14, field: 'member.nickname', default: '-')]
    public string $nickname;

    #[ExportColumn(title: '数量', order: 5, type: 'int', width: 8)]
    public int $quantity;

    #[ExportColumn(title: '原价(元)', order: 6, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $original_price;

    #[ExportColumn(title: '秒杀价(元)', order: 7, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $seckill_price;

    #[ExportColumn(title: '总金额(元)', order: 8, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $total_amount;

    #[ExportColumn(title: '状态', order: 9, width: 10, enum: ['pending' => '待支付', 'paid' => '已支付', 'cancelled' => '已取消', 'completed' => '已完成', 'refunded' => '已退款'])]
    public string $status;

    #[ExportColumn(title: '秒杀时间', order: 10, width: 20, default: '-')]
    public string $seckill_time;

    #[ExportColumn(title: '支付时间', order: 11, width: 20, default: '-')]
    public string $pay_time;

    #[ExportColumn(title: '创建时间', order: 12, width: 20)]
    public string $created_at;
}
