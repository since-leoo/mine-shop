<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Infrastructure\Plugin\Repository\CommerceOrderRepository;
use App\Domain\Infrastructure\Plugin\ValueObject\ShipmentAllocationVo;

final class DomainCommerceOrderService
{
    public function __construct(private readonly CommerceOrderRepository $repository) {}
    public function find(int $id): ?array { return $this->repository->find($id); }
    public function findByNumber(string $number): ?array { return $this->repository->findByNumber($number); }
    public function paidSince(string $provider, int $afterId, int $limit): array { return $this->repository->paidSince($provider, $afterId, $limit); }

    /** 管理端混合订单只分配自营订单项；多个包裹必须明确数量，不能推断供应商履约。 */
    public function shipMerchantIfExternal(int $orderId, array $packages): ?array
    {
        $order = $this->repository->find($orderId, true);
        if ($order === null) { throw new \DomainException('订单不存在'); }
        $hasExternal = array_filter($order['items'], static fn ($item) => ($item['fulfillment_provider'] ?? 'merchant') !== 'merchant');
        if ($hasExternal === []) { return null; }
        if ($packages === []) { throw new \InvalidArgumentException('请提供自营商品包裹'); }
        $shipped = $this->repository->shippedQuantities($orderId);
        $remaining = [];
        foreach ($order['items'] as $item) {
            $quantity = (int) $item['quantity'] - (int) ($shipped[$item['id']] ?? 0);
            if (($item['fulfillment_provider'] ?? 'merchant') === 'merchant' && $quantity > 0) {
                $remaining[] = ['order_item_id' => (int) $item['id'], 'quantity' => $quantity];
            }
        }
        foreach ($packages as $package) {
            $items = $package['items'] ?? (count($packages) === 1 ? $remaining : []);
            if ($items === []) { throw new \DomainException('没有待发自营商品，或多包裹缺少逐项数量'); }
            $key = 'merchant:' . (string) ($package['shipping_company'] ?? '') . ':' . (string) ($package['shipping_no'] ?? '');
            $this->ship($orderId, 'merchant', $key, $package, $items);
        }
        return $this->repository->find($orderId);
    }

    public function ship(int $orderId, string $provider, string $shipmentKey, array $package, array $items): array
    {
        if ($provider !== 'merchant') { DomainCommerceProductService::validateProvider($provider); }
        if ($shipmentKey === '') { throw new \InvalidArgumentException('包裹幂等键不能为空'); }
        $order = $this->repository->find($orderId, true);
        if ($order === null) { throw new \DomainException('订单不存在'); }
        $key = hash('sha256', $shipmentKey);
        $existing = $this->repository->package($orderId, $provider, $key);
        if ($existing !== null) {
            $quantities = [];
            foreach ($items as $item) {
                $id = (int) ($item['order_item_id'] ?? 0);
                $quantities[$id] = ($quantities[$id] ?? 0) + (int) ($item['quantity'] ?? 0);
            }
            if ($existing['integration_digest'] !== ShipmentAllocationVo::digest($package, $quantities)) {
                throw new \DomainException('同一外部包裹不能改变发货明细或运单');
            }
            return $existing + ['created' => false];
        }
        if (! in_array($order['status'], ['paid', 'partial_shipped'], true)) {
            throw new \DomainException('当前订单状态不可发货');
        }
        $allocation = new ShipmentAllocationVo($order['items'], $this->repository->shippedQuantities($orderId), $provider, $package, $items);
        if ($this->repository->hasOpenAfterSales(array_keys($allocation->quantities))) {
            throw new \DomainException('包裹商品存在售后，须核对后再同步履约');
        }
        return $this->repository->savePackage($orderId, [
            'fulfillment_provider' => $provider, 'integration_key' => $key, 'integration_digest' => $allocation->digest,
            'package_no' => 'EXT' . substr(hash('sha256', $provider . ':' . $orderId . ':' . $shipmentKey), 0, 29),
            'express_company' => trim($package['shipping_company']), 'express_no' => trim($package['shipping_no']),
            'remark' => (string) ($package['remark'] ?? ''), 'status' => 'shipped', 'shipped_at' => date('Y-m-d H:i:s'),
        ], $allocation->quantities, $allocation->status) + ['created' => true];
    }
}
