<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

/** 插件实现的通用履约能力。停用时 fallback 必须拒绝外部商品交易。 */
interface ExternalFulfillmentInterface
{
    /** 纯本地能力/连接状态检查；snapshot 可为 sku_id 或冻结订单履约快照。 */
    public function assertAvailable(string $provider, array $snapshot = []): void;

    /** @param array<int,array> $items 核心可信 SKU 快照，附 quantity 和 fulfillment_provider
     * @return array{items:array<int,array{provider:string,snapshot:array}>,shipping_fee:int} 按本地 SKU ID 索引 */
    public function prepare(array $items, array $address, string $orderType): array;
}
