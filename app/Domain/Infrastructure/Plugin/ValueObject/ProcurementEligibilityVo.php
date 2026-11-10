<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\ValueObject;

/** 首次外部采购必须基于核心持久状态校验；插件异步售后事件仅用于补偿。 */
final class ProcurementEligibilityVo
{
    public static function assertCanProtect(array $order, string $provider, array $requested): void
    {
        if (($order['pay_status'] ?? '') !== 'paid' || ! in_array($order['status'] ?? '', ['paid', 'partial_shipped'], true)) {
            throw new \DomainException('销售订单未支付、已取消、已退款或已履约，不能新增采购');
        }
        $quantities = [];
        foreach ($requested as $item) {
            $id = $item['sku_id'] ?? 0;
            $quantity = $item['quantity'] ?? 0;
            if (! is_int($id) || $id < 1 || ! is_int($quantity) || $quantity < 1) {
                throw new \InvalidArgumentException('采购保护SKU和数量必须为正整数');
            }
            $quantities[$id] = ($quantities[$id] ?? 0) + $quantity;
        }
        if ($quantities === []) { throw new \InvalidArgumentException('采购保护明细不能为空'); }
        $expected = [];
        $itemIds = [];
        foreach ($order['items'] as $item) {
            $skuId = (int) $item['sku_id'];
            if (! isset($quantities[$skuId])) { continue; }
            $snapshot = $item['fulfillment_snapshot'] ?? [];
            if (is_string($snapshot)) { $snapshot = json_decode($snapshot, true, 512, JSON_THROW_ON_ERROR); }
            if (($item['fulfillment_provider'] ?? 'merchant') !== $provider || ! is_array($snapshot) || $snapshot === []) {
                throw new \DomainException('采购商品缺少对应履约快照或归属不一致');
            }
            $expected[$skuId] = ($expected[$skuId] ?? 0) + (int) $item['quantity'];
            $itemIds[(int) $item['id']] = true;
        }
        ksort($quantities); ksort($expected);
        if ($quantities !== $expected) { throw new \DomainException('采购明细与销售订单商品或数量不一致'); }
        foreach ($order['after_sales'] ?? [] as $sale) {
            if (isset($itemIds[(int) $sale['order_item_id']]) && (($sale['status'] ?? '') !== 'closed' || ($sale['refund_status'] ?? '') === 'refunded')) {
                throw new \DomainException('采购商品已申请售后或已退款，需先核实售后结果');
            }
        }
    }
}
