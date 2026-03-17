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

namespace App\Application\Admin\Trade;

use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;

final class AppAfterSaleQueryService
{
    public function __construct(private readonly AfterSaleRepository $afterSaleRepository) {}

    /**
     * 鍚庡彴鍒嗛〉鏌ヨ鍞悗鍗曞垪琛ㄣ€?     *
     * @return array{list: array<int, array<string, mixed>>, total: int}
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        $result = $this->afterSaleRepository->pageForAdmin($filters, $page, $pageSize);
        $result['list'] = array_map(fn (array $item) => $this->transformListItem($item), $result['list']);

        return $result;
    }

    /**
     * 鏌ヨ鍚庡彴鍞悗璇︽儏銆?     *
     * @return array<string, mixed>|null
     */
    public function detail(int $id): ?array
    {
        $model = $this->afterSaleRepository->findDetailById($id);
        if ($model === null) {
            return null;
        }

        return $this->transformDetail($model);
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
     * @return array<string, mixed>
     */
    private function transformDetail(AfterSale $model): array
    {
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
}