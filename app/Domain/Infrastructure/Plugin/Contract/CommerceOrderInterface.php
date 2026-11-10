<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface CommerceOrderInterface
{
    /** 包含 items[*].fulfillment_provider/fulfillment_snapshot 和 after_sales 标量记录。
     * after_sales: [{id,order_item_id,type,status,refund_status,quantity,apply_amount,refund_amount}]。
     * 查询仅供编排展示；首次采购仍须调用库存 protect 进行订单锁下最终校验。 */
    public function find(int $orderId): ?array;
    public function findByNumber(string $orderNo): ?array;
    /** 已支付订单补偿扫描：按订单 ID 游标升序，包含冻结的履约快照。 */
    public function paidSince(string $provider, int $afterId, int $limit = 100): array;
    /** @param array{shipping_company:string,shipping_no:string,remark?:string} $package
     *  @param array<int,array{order_item_id:int,quantity:int}> $items */
    public function ship(int $orderId, string $provider, string $shipmentKey, array $package, array $items): array;
}
