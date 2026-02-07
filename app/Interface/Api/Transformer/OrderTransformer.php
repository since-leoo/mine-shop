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

namespace App\Interface\Api\Transformer;

use App\Domain\Order\Enum\OrderStatus;
use App\Infrastructure\Model\Order\Order;

final class OrderTransformer
{
    /**
     * 转换订单（含默认 includes: items, address）.
     */
    public function transform(Order $order): array
    {
        $data = $this->transformBase($order);
        $data['items'] = $this->transformItems($order);
        $data['address'] = $this->transformAddress($order);
        $data['buttonVOs'] = $this->resolveButtons($order->status);
        $data['orderStatusName'] = $this->resolveStatusName($order->status);

        return $data;
    }

    /**
     * 转换订单详情（含所有 includes）.
     */
    public function transformDetail(Order $order): array
    {
        $data = $this->transform($order);
        $data['packages'] = $this->transformPackages($order);
        $data['logs'] = $this->transformLogs($order);

        return $data;
    }

    /**
     * 根据订单状态返回操作按钮列表.
     *
     * type: 1=付款, 2=取消订单, 3=确认收货, 6=评价, 7=删除订单, 8=查看物流, 9=再次购买
     * 售后相关按钮(4,5)暂不返回
     */
    private function resolveButtons(string $status): array
    {
        return match ($status) {
            OrderStatus::PENDING->value => [
                ['type' => 2, 'name' => '取消订单', 'primary' => false],
                ['type' => 1, 'name' => '付款', 'primary' => true],
            ],
            OrderStatus::PAID->value => [
                ['type' => 9, 'name' => '再次购买', 'primary' => true],
            ],
            OrderStatus::SHIPPED->value,
            OrderStatus::PARTIAL_SHIPPED->value => [
                ['type' => 8, 'name' => '查看物流', 'primary' => false],
                ['type' => 3, 'name' => '确认收货', 'primary' => true],
            ],
            OrderStatus::COMPLETED->value => [
                ['type' => 9, 'name' => '再次购买', 'primary' => false],
                ['type' => 6, 'name' => '评价', 'primary' => true],
            ],
            default => [],
        };
    }

    private function resolveStatusName(string $status): string
    {
        return match ($status) {
            OrderStatus::PENDING->value => '待付款',
            OrderStatus::PAID->value => '待发货',
            OrderStatus::PARTIAL_SHIPPED->value => '部分发货',
            OrderStatus::SHIPPED->value => '待收货',
            OrderStatus::COMPLETED->value => '已完成',
            OrderStatus::CANCELLED->value => '已取消',
            OrderStatus::REFUNDED->value => '已退款',
            default => '未知',
        };
    }

    private function transformBase(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNo' => $order->order_no,
            'orderType' => $order->order_type,
            'status' => $order->status,
            'goodsAmount' => $order->goods_amount,
            'shippingFee' => $order->shipping_fee,
            'discountAmount' => $order->discount_amount,
            'totalAmount' => $order->total_amount,
            'payAmount' => $order->pay_amount,
            'payStatus' => $order->pay_status,
            'payTime' => $order->pay_time?->toDateTimeString(),
            'payNo' => $order->pay_no,
            'payMethod' => $order->pay_method,
            'buyerRemark' => $order->buyer_remark,
            'sellerRemark' => $order->seller_remark,
            'shippingStatus' => $order->shipping_status,
            'packageCount' => $order->package_count,
            'expireTime' => $order->expire_time?->toDateTimeString(),
            'createdAt' => $order->created_at->toDateTimeString(),
            'updatedAt' => $order->updated_at->toDateTimeString(),
        ];
    }

    private function transformItems(Order $order): array
    {
        if (! $order->relationLoaded('items')) {
            return [];
        }

        return $order->items->map(static fn ($item) => [
            'id' => $item->id,
            'productId' => $item->product_id,
            'skuId' => $item->sku_id,
            'productName' => $item->product_name,
            'skuName' => $item->sku_name,
            'productImage' => $item->product_image,
            'unitPrice' => $item->unit_price,
            'quantity' => $item->quantity,
            'totalPrice' => $item->total_price,
        ])->toArray();
    }

    private function transformAddress(Order $order): ?array
    {
        if (! $order->relationLoaded('address') || ! $order->address) {
            return null;
        }

        $address = $order->address;

        return [
            'id' => $address->id,
            'name' => $address->name,
            'phone' => $address->phone,
            'province' => $address->province,
            'city' => $address->city,
            'district' => $address->district,
            'detail' => $address->detail,
            'fullAddress' => $address->full_address,
        ];
    }

    private function transformPackages(Order $order): array
    {
        if (! $order->relationLoaded('packages')) {
            return [];
        }

        return $order->packages->map(static fn ($package) => [
            'id' => $package->id,
            'packageNo' => $package->package_no,
            'expressCompany' => $package->express_company,
            'expressNo' => $package->express_no,
            'status' => $package->status,
            'shippedAt' => $package->shipped_at?->toDateTimeString(),
            'deliveredAt' => $package->delivered_at?->toDateTimeString(),
        ])->toArray();
    }

    private function transformLogs(Order $order): array
    {
        if (! $order->relationLoaded('logs')) {
            return [];
        }

        return $order->logs->map(static fn ($log) => [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'operatorType' => $log->operator_type,
            'operatorId' => $log->operator_id,
            'operatorName' => $log->operator_name,
            'createdAt' => $log->created_at->toDateTimeString(),
        ])->toArray();
    }
}
