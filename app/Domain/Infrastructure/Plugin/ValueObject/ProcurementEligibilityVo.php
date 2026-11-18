<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\ValueObject;

use App\Infrastructure\Error\CommerceError;

/** 首次外部采购必须基于核心持久状态校验；插件异步售后事件仅用于补偿。 */
final class ProcurementEligibilityVo
{
    public static function assertCanProtect(array $order, string $provider, array $requested): void
    {
        if (($order['pay_status'] ?? '') !== 'paid' || ! in_array($order['status'] ?? '', ['paid', 'partial_shipped'], true)) {
            throw CommerceError::PROCUREMENT_ORDER_INELIGIBLE->exception();
        }
        $quantities = [];
        foreach ($requested as $item) {
            $id = $item['sku_id'] ?? 0;
            $quantity = $item['quantity'] ?? 0;
            if (! is_int($id) || $id < 1 || ! is_int($quantity) || $quantity < 1) {
                throw CommerceError::PROCUREMENT_QUANTITY_INVALID->invalid();
            }
            $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
        }
        if ($quantities === []) { throw CommerceError::PROCUREMENT_ITEMS_REQUIRED->invalid(); }
        $expected = [];
        $itemIds = [];
        foreach ($order['items'] as $item) {
            $skuId = (int) $item['sku_id'];
            if (! isset($quantities[$skuId])) { continue; }
            $snapshot = $item['fulfillment_snapshot'] ?? [];
            if (is_string($snapshot)) { $snapshot = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR); }
            if (($item['fulfillment_provider'] ?? 'merchant') !== $provider || ! is_array($snapshot) || $snapshot === []) {
                throw CommerceError::PROCUREMENT_SNAPSHOT_INVALID->exception();
            }
            $expected[$skuId] = ($expected[$skuId] ?? 0) + (int) $item['quantity'];
            $itemIds[(int) $item['id']] = true;
        }
        ksort($quantities); ksort($expected);
        if ($quantities !== $expected) { throw CommerceError::PROCUREMENT_ITEMS_MISMATCH->exception(); }
        foreach ($order['after_sales'] ?? [] as $sale) {
            if (isset($itemIds[(int) $sale['order_item_id']]) && (($sale['status'] ?? '') !== 'closed' || ($sale['refund_status'] ?? '') === 'refunded')) {
                throw CommerceError::PROCUREMENT_AFTER_SALE_CONFLICT->exception();
            }
        }
    }
}
