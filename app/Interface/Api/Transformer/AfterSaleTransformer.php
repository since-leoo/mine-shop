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

use App\Infrastructure\Model\AfterSale\AfterSale;

final class AfterSaleTransformer
{
    /**
     * 将售后模型转换为小程序可直接消费的结构。
     */
    public function transform(AfterSale $afterSale): array
    {
        return [
            'id' => (int) $afterSale->id,
            'afterSaleNo' => (string) $afterSale->after_sale_no,
            'orderId' => (int) $afterSale->order_id,
            'orderNo' => (string) ($afterSale->order?->order_no ?? ''),
            'orderItemId' => (int) $afterSale->order_item_id,
            'type' => (string) $afterSale->type,
            'status' => (string) $afterSale->status,
            'refundStatus' => (string) $afterSale->refund_status,
            'returnStatus' => (string) $afterSale->return_status,
            'applyAmount' => (int) $afterSale->apply_amount,
            'refundAmount' => (int) $afterSale->refund_amount,
            'quantity' => (int) $afterSale->quantity,
            'reason' => (string) $afterSale->reason,
            'description' => $afterSale->description,
            'rejectReason' => $afterSale->reject_reason,
            'images' => $afterSale->images ?? [],
            'buyerReturnLogisticsCompany' => $afterSale->buyer_return_logistics_company,
            'buyerReturnLogisticsNo' => $afterSale->buyer_return_logistics_no,
            'reshipLogisticsCompany' => $afterSale->reship_logistics_company,
            'reshipLogisticsNo' => $afterSale->reship_logistics_no,
            'product' => [
                'productId' => (int) ($afterSale->orderItem?->product_id ?? 0),
                'skuId' => (int) ($afterSale->orderItem?->sku_id ?? 0),
                'productName' => (string) ($afterSale->orderItem?->product_name ?? ''),
                'skuName' => (string) ($afterSale->orderItem?->sku_name ?? ''),
                'productImage' => (string) ($afterSale->orderItem?->product_image ?? ''),
            ],
            'createdAt' => $afterSale->created_at?->toDateTimeString(),
            'updatedAt' => $afterSale->updated_at?->toDateTimeString(),
        ];
    }
}