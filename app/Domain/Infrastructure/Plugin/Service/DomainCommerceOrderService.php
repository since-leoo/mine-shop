<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Infrastructure\Plugin\Repository\CommerceOrderRepository;

use App\Domain\Infrastructure\Plugin\ValueObject\ShipmentAllocationVo;
use App\Infrastructure\Error\CommerceError;

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
        if ($order === null) { throw CommerceError::ORDER_NOT_FOUND->exception(); }
        $hasExternal = array_filter($order['items'], static fn ($item) => ($item['fulfillment_provider'] ?? 'merchant') !== 'merchant');
        if ($hasExternal === []) { return null; }
        if ($packages === []) { throw CommerceError::MERCHANT_PACKAGE_REQUIRED->invalid(); }
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
            if ($items === []) { throw CommerceError::PACKAGE_ALLOCATION_REQUIRED->exception(); }
            $key = 'merchant:' . (string) ($package['shipping_company'] ?? '') . ':' . (string) ($package['shipping_no'] ?? '');
            $this->ship($orderId, 'merchant', $key, $package, $items);
        }
        return $this->repository->find($orderId);
    }

    public function ship(int $orderId, string $provider, string $shipmentKey, array $package, array $items): array
    {
        if ($provider !== 'merchant') { DomainCommerceProductService::validateProvider($provider); }
        if ($shipmentKey === '') { throw CommerceError::PACKAGE_KEY_REQUIRED->invalid(); }
        $order = $this->repository->find($orderId, true);
        if ($order === null) { throw CommerceError::ORDER_NOT_FOUND->exception(); }
        $key = hash('sha256', $shipmentKey);
        $existing = $this->repository->package($orderId, $provider, $key);
        if ($existing !== null) {
            $quantities = [];
            foreach ($items as $item) {
                $id = (int) ($item['order_item_id'] ?? 0);
                $quantities[$id] = ($quantities[$id] ?? 0) + (int) ($item['quantity'] ?? 0);
            }
            if ($existing['integration_digest'] !== ShipmentAllocationVo::digest($package, $quantities)) {
                throw CommerceError::PACKAGE_IDEMPOTENCY_CONFLICT->exception();
            }
            return $existing + ['created' => false];
        }
        if (! in_array($order['status'], ['paid', 'partial_shipped'], true)) {
            throw CommerceError::ORDER_NOT_SHIPPABLE->exception();
        }
        $allocation = new ShipmentAllocationVo($order['items'], $this->repository->shippedQuantities($orderId), $provider, $package, $items);
        if ($this->repository->hasOpenAfterSales(array_keys($allocation->quantities))) {
            throw CommerceError::SHIPMENT_AFTER_SALE_CONFLICT->exception();
        }
        return $this->repository->savePackage($orderId, [
            'fulfillment_provider' => $provider, 'integration_key' => $key, 'integration_digest' => $allocation->digest,
            'package_no' => 'EXT' . substr(hash('sha256', $provider . ':' . $orderId . ':' . $shipmentKey), 0, 29),
            'express_company' => trim($package['shipping_company']), 'express_no' => trim($package['shipping_no']),
            'remark' => (string) ($package['remark'] ?? ''), 'status' => 'shipped', 'shipped_at' => date('Y-m-d H:i:s'),
        ], $allocation->quantities, $allocation->status) + ['created' => true];
    }
}
