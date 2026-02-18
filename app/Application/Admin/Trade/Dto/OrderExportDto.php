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

namespace App\Application\Admin\Trade\Dto;

use App\Domain\Trade\Order\Repository\OrderRepository;
use Plugin\ExportCenter\Annotation\ExportColumn;
use Plugin\ExportCenter\Annotation\ExportSheet;

#[ExportSheet(
    name: '订单列表',
    description: '订单导出（含商品明细）',
    dataProvider: [OrderRepository::class, 'getExportData'],
)]
class OrderExportDto
{
    #[ExportColumn(title: '订单编号', order: 1, width: 22, field: 'order_no')]
    public string $order_no;

    #[ExportColumn(title: '会员昵称', order: 2, width: 14, field: 'member.nickname', default: '-')]
    public string $nickname;

    #[ExportColumn(title: '会员手机', order: 3, width: 14, field: 'member.phone', default: '-')]
    public string $phone;

    #[ExportColumn(title: '订单类型', order: 4, width: 10, field: 'order_type', enum: ['normal' => '普通订单', 'seckill' => '秒杀订单', 'group_buy' => '拼团订单'], default: '普通订单')]
    public string $order_type;

    #[ExportColumn(title: '订单状态', order: 5, width: 12, field: 'status', enum: ['pending' => '待付款', 'paid' => '已付款', 'partial_shipped' => '部分发货', 'shipped' => '已发货', 'completed' => '已完成', 'cancelled' => '已取消', 'refunded' => '已退款'])]
    public string $status;

    #[ExportColumn(title: '支付状态', order: 6, width: 12, field: 'pay_status', enum: ['pending' => '待支付', 'paid' => '已支付', 'failed' => '支付失败', 'cancelled' => '已取消', 'refunded' => '已退款'])]
    public string $pay_status;

    #[ExportColumn(title: '商品名称', order: 7, width: 24, field: 'product_name', default: '-')]
    public string $product_name;

    #[ExportColumn(title: 'SKU名称', order: 8, width: 18, field: 'sku_name', default: '-')]
    public string $sku_name;

    #[ExportColumn(title: '规格', order: 9, width: 18, field: 'spec_values', default: '-', glue: '/')]
    public string $spec_values;

    #[ExportColumn(title: '单价(元)', type: 'float', order: 10, width: 12, format: '#,##0.00', field: 'unit_price', divisor: 100)]
    public float $unit_price;

    #[ExportColumn(title: '数量', type: 'int', order: 11, width: 8, field: 'quantity', default: '0')]
    public int $quantity;

    #[ExportColumn(title: '小计(元)', type: 'float', order: 12, width: 12, format: '#,##0.00', field: 'total_price', divisor: 100)]
    public float $item_total;

    #[ExportColumn(title: '商品金额(元)', type: 'float', order: 13, width: 14, format: '#,##0.00', field: 'goods_amount', divisor: 100)]
    public float $goods_amount;

    #[ExportColumn(title: '运费(元)', type: 'float', order: 14, width: 12, format: '#,##0.00', field: 'shipping_fee', divisor: 100)]
    public float $shipping_fee;

    #[ExportColumn(title: '优惠金额(元)', type: 'float', order: 15, width: 14, format: '#,##0.00', field: 'discount_amount', divisor: 100)]
    public float $discount_amount;

    #[ExportColumn(title: '订单总额(元)', type: 'float', order: 16, width: 14, format: '#,##0.00', field: 'total_amount', divisor: 100)]
    public float $total_amount;

    #[ExportColumn(title: '实付金额(元)', type: 'float', order: 17, width: 14, format: '#,##0.00', field: 'pay_amount', divisor: 100)]
    public float $pay_amount;

    #[ExportColumn(title: '支付方式', order: 18, width: 12, field: 'pay_method', enum: ['wechat' => '微信支付', 'balance' => '余额支付'], default: '-')]
    public string $pay_method;

    #[ExportColumn(title: '支付流水号', order: 19, width: 22, field: 'pay_no', default: '-')]
    public string $pay_no;

    #[ExportColumn(title: '支付时间', order: 20, width: 20, field: 'pay_time', default: '-')]
    public string $pay_time;

    #[ExportColumn(title: '收货人', order: 21, width: 10, field: 'address.name', default: '-')]
    public string $receiver_name;

    #[ExportColumn(title: '收货电话', order: 22, width: 14, field: 'address.phone', default: '-')]
    public string $receiver_phone;

    #[ExportColumn(title: '收货地址', order: 23, width: 36, field: 'address.full_address', default: '-', wrapText: true)]
    public string $receiver_address;

    #[ExportColumn(title: '买家备注', order: 24, width: 20, field: 'buyer_remark', wrapText: true)]
    public string $buyer_remark;

    #[ExportColumn(title: '卖家备注', order: 25, width: 20, field: 'seller_remark', wrapText: true)]
    public string $seller_remark;

    #[ExportColumn(title: '下单时间', order: 26, width: 20, field: 'created_at')]
    public string $created_at;
}
