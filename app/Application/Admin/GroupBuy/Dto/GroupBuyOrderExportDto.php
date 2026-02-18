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

use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '拼团订单',
    description: '拼团订单导出',
    dataProvider: [GroupBuyOrderRepository::class, 'getExportData'],
)]
class GroupBuyOrderExportDto
{
    #[ExportColumn(title: 'ID', order: 1, type: 'int', width: 8)]
    public int $id;

    #[ExportColumn(title: '团购活动', order: 2, width: 20, field: 'group_buy.title', default: '-')]
    public string $group_buy_title;

    #[ExportColumn(title: '关联订单ID', order: 3, type: 'int', width: 12)]
    public int $order_id;

    #[ExportColumn(title: '会员昵称', order: 4, width: 14, field: 'member.nickname', default: '-')]
    public string $nickname;

    #[ExportColumn(title: '团号', order: 5, width: 18)]
    public string $group_no;

    #[ExportColumn(title: '是否团长', order: 6, width: 10, type: 'boolean')]
    public string $is_leader;

    #[ExportColumn(title: '数量', order: 7, type: 'int', width: 8)]
    public int $quantity;

    #[ExportColumn(title: '原价(元)', order: 8, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $original_price;

    #[ExportColumn(title: '团购价(元)', order: 9, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $group_price;

    #[ExportColumn(title: '总金额(元)', order: 10, type: 'float', width: 12, format: '#,##0.00', divisor: 100)]
    public float $total_amount;

    #[ExportColumn(title: '状态', order: 11, width: 10, enum: ['pending' => '待支付', 'paid' => '已支付', 'grouping' => '拼团中', 'success' => '拼团成功', 'failed' => '拼团失败', 'cancelled' => '已取消', 'refunded' => '已退款'])]
    public string $status;

    #[ExportColumn(title: '参团时间', order: 12, width: 20, default: '-')]
    public string $join_time;

    #[ExportColumn(title: '支付时间', order: 13, width: 20, default: '-')]
    public string $pay_time;

    #[ExportColumn(title: '创建时间', order: 14, width: 20)]
    public string $created_at;
}
