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

namespace App\Interface\Admin\Transformer\Order;

use App\Infrastructure\Model\AfterSale\AfterSale;

final class AfterSaleTransformer
{
    /**
     * @param array{list: array<int, array<string, mixed>>, total: int} $result
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function transformPageResult(array $result): array
    {
        return [
            'list' => array_map(fn (array $item) => $this->transformListItem($item), $result['list']),
            'total' => (int) $result['total'],
        ];
    }

    /**
     * @param array{after_sale: object, refund_record: object|null} $result
     * @return array<string, mixed>
     */
    public function transformDetailResult(array $result): array
    {
        /** @var AfterSale $model */
        $model = $result['after_sale'];

        return [
            'id' => (int) $model->id,
            'after_sale_no' => $this->resolveAfterSaleNo([
                'id' => (int) $model->id,
                'after_sale_no' => (string) $model->after_sale_no,
                'created_at' => $model->getRawOriginal('created_at') ?: null,
            ]),
            'order_id' => (int) $model->order_id,
            'order_no' => (string) ($model->order?->order_no ?? ''),
            'order_item_id' => (int) $model->order_item_id,
            'member_id' => (int) $model->member_id,
            'type' => (string) $model->type,
            'status' => (string) $model->status,
            'refund_status' => (string) $model->refund_status,
            'return_status' => (string) $model->return_status,
            'apply_amount' => (int) $model->apply_amount,
            'refund_amount' => (int) $model->refund_amount,
            'quantity' => (int) $model->quantity,
            'reason' => (string) $model->reason,
            'description' => $model->description,
            'reject_reason' => $model->reject_reason,
            'images' => $model->images ?? [],
            'buyer_return_logistics_company' => $model->buyer_return_logistics_company,
            'buyer_return_logistics_no' => $model->buyer_return_logistics_no,
            'reship_logistics_company' => $model->reship_logistics_company,
            'reship_logistics_no' => $model->reship_logistics_no,
            'refund_record' => $this->transformRefundRecord($result['refund_record']),
            'product' => [
                'productId' => (int) ($model->orderItem?->product_id ?? 0),
                'skuId' => (int) ($model->orderItem?->sku_id ?? 0),
                'productName' => (string) ($model->orderItem?->product_name ?? ''),
                'skuName' => (string) ($model->orderItem?->sku_name ?? ''),
                'productImage' => (string) ($model->orderItem?->product_image ?? ''),
            ],
            'created_at' => $model->getRawOriginal('created_at'),
            'updated_at' => $model->getRawOriginal('updated_at'),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function transformListItem(array $item): array
    {
        $orderItem = (array) ($item['order_item'] ?? []);
        $order = (array) ($item['order'] ?? []);

        return [
            'id' => (int) ($item['id'] ?? 0),
            'after_sale_no' => $this->resolveAfterSaleNo($item),
            'order_id' => (int) ($item['order_id'] ?? 0),
            'order_no' => (string) ($order['order_no'] ?? ''),
            'order_item_id' => (int) ($item['order_item_id'] ?? 0),
            'member_id' => (int) ($item['member_id'] ?? 0),
            'type' => (string) ($item['type'] ?? ''),
            'status' => (string) ($item['status'] ?? ''),
            'refund_status' => (string) ($item['refund_status'] ?? ''),
            'return_status' => (string) ($item['return_status'] ?? ''),
            'apply_amount' => (int) ($item['apply_amount'] ?? 0),
            'refund_amount' => (int) ($item['refund_amount'] ?? 0),
            'quantity' => (int) ($item['quantity'] ?? 0),
            'reason' => (string) ($item['reason'] ?? ''),
            'description' => $item['description'] ?? null,
            'reject_reason' => $item['reject_reason'] ?? null,
            'images' => $item['images'] ?? [],
            'buyer_return_logistics_company' => $item['buyer_return_logistics_company'] ?? null,
            'buyer_return_logistics_no' => $item['buyer_return_logistics_no'] ?? null,
            'reship_logistics_company' => $item['reship_logistics_company'] ?? null,
            'reship_logistics_no' => $item['reship_logistics_no'] ?? null,
            'product' => [
                'productId' => (int) ($orderItem['product_id'] ?? 0),
                'skuId' => (int) ($orderItem['sku_id'] ?? 0),
                'productName' => (string) ($orderItem['product_name'] ?? ''),
                'skuName' => (string) ($orderItem['sku_name'] ?? ''),
                'productImage' => (string) ($orderItem['product_image'] ?? ''),
            ],
            'created_at' => $item['created_at'] ?? null,
            'updated_at' => $item['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveAfterSaleNo(array $item): string
    {
        $afterSaleNo = trim((string) ($item['after_sale_no'] ?? ''));
        if ($afterSaleNo !== '') {
            return $afterSaleNo;
        }

        return AfterSale::generateAfterSaleNo(
            isset($item['created_at']) ? (string) $item['created_at'] : null,
            isset($item['id']) ? (int) $item['id'] : null,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transformRefundRecord(?object $refund): ?array
    {
        if ($refund === null) {
            return null;
        }

        return [
            'refund_no' => (string) ($refund->refund_no ?? ''),
            'status' => (string) ($refund->status ?? ''),
            'refund_amount' => (int) ($refund->refund_amount ?? 0),
            'refund_reason' => $refund->refund_reason ?? null,
            'third_party_refund_no' => $refund->third_party_refund_no ?? null,
            'remark' => $refund->remark ?? null,
            'processed_at' => $refund->processed_at?->toDateTimeString(),
        ];
    }
}
