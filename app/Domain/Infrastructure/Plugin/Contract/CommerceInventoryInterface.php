<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

/** 外部库存投影仍使用商城同一库存 Hash 与 Lua 预占。 */
interface CommerceInventoryInterface
{
    /** version 是单调递增的上游快照版本；过期或重复快照不会增加可售库存。 */
    public function project(string $provider, int $skuId, int $upstreamStock, int $version): int;
    /** 仅用于上游已证明包含指定采购扣减的权威快照；调用方须保留审核与证明日志。 */
    public function reconcile(string $provider, int $skuId, int $upstreamStock, int $version, array $acknowledgedTradeNos): int;
    /** 外部POST前锁核心订单并检查支付/取消/退款/目标项售后，再保护有效原预占；已释放则拒绝。支持SKU分组。 */
    public function protect(string $provider, string $tradeNo, array $items): void;
    /** 仅在明确未发送/明确拒绝后解除；不增加当前可售库存，已确认采购不得解除。 */
    public function clearProtection(string $provider, string $tradeNo, array $items): void;
    /** @param array<int,array{sku_id:int,quantity:int}> $items 确认采购后禁止普通取消返还外部库存 */
    public function acknowledge(string $provider, string $tradeNo, array $items): void;
}
