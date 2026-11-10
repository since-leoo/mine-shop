<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

/** 核心商品公开端口：金额均为分；仅传输数组，不暴露持久化模型。 */
interface CommerceProductInterface
{
    /** @return array{product_id:int, skus:array, created:bool} */
    public function import(string $provider, string $idempotencyKey, array $product): array;
    public function find(int $productId): ?array;
    /** 按 sku_code 幂等增规，已有规格及商品状态不变；新增库存固定为0等待投影。返回完整商品。 */
    public function appendSkus(int $productId, string $provider, array $skus): array;
    /** SKU 按本地 id 合并，不能通过同步删除既有身份。库存须走独立端口。 */
    public function sync(int $productId, string $provider, array $changes): array;
}
