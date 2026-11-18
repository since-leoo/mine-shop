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

namespace Plugin\SupplyChain\Domain\Service;

use App\Domain\Infrastructure\Plugin\Contract\CommerceInventoryInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface;
use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Domain\Entity\PurchaseEntity;
use Plugin\SupplyChain\Domain\Mapper\PurchaseMapper;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;

final class DomainSupplyPurchaseService
{
    public function __construct(private readonly SupplyRepository $repository, private readonly DomainSupplyTaskService $tasks, private readonly CommerceOrderInterface $orders, private readonly CommerceInventoryInterface $inventory) {}

    public function getEntity(int $id, bool $lock = false): PurchaseEntity
    {
        return PurchaseMapper::fromModel($this->repository->require('orders', $id, $lock));
    }

    public function ensure(int $orderId): array
    {
        $order = $this->orders->find($orderId) ?? throw SupplyChainError::SALES_ORDER_NOT_FOUND->internal();
        if (($order['pay_status'] ?? '') !== 'paid') {
            return [];
        }
        $groups = [];
        foreach ($order['items'] ?? [] as $item) {
            if (($item['fulfillment_provider'] ?? 'merchant') !== 'supply_chain') {
                continue;
            }
            $snapshot = $item['fulfillment_snapshot'] ?? [];
            if (\is_string($snapshot)) {
                $snapshot = json_decode($snapshot, true, 512, \JSON_THROW_ON_ERROR);
            }
            if (empty($snapshot['connection_id']) || empty($snapshot['external_sku_id'])) {
                throw SupplyChainError::FROZEN_ROUTE_MISSING->exception();
            }
            $groups[(int) $snapshot['connection_id']][] = ['order_item_id' => (int) $item['id'], 'sku_id' => (int) $item['sku_id'], 'quantity' => (int) $item['quantity'], 'snapshot' => $snapshot];
        }
        $ids = [];
        $now = date('Y-m-d H:i:s');
        foreach ($groups as $connection => $items) {
            $groupKey = 'connection:' . $connection;
            $merchant = 'SC' . $orderId . '-' . $connection . '-' . mb_substr(hash('sha256', $order['order_no'] . ':' . $connection), 0, 12);
            $this->repository->query('orders')->insertOrIgnore($this->repository->encode(['connection_id' => $connection, 'order_id' => $orderId, 'order_no' => $order['order_no'], 'merchant_order_no' => $merchant, 'group_key' => $groupKey, 'status' => 'pending', 'snapshot' => ['items' => $items, 'address' => $order['address'] ?? []], 'created_at' => $now, 'updated_at' => $now]));
            $purchase = $this->repository->decode($this->repository->query('orders')->where('order_id', $orderId)->where('connection_id', $connection)->where('group_key', $groupKey)->first());
            foreach ($items as $item) {
                $snapshot = $item['snapshot'];
                $this->repository->query('order_items')->insertOrIgnore(['supply_order_id' => $purchase['id'], 'order_item_id' => $item['order_item_id'], 'sku_id' => $item['sku_id'], 'external_product_id' => $snapshot['external_product_id'], 'external_sku_id' => $snapshot['external_sku_id'], 'quantity' => $item['quantity'], 'cost_price' => $snapshot['cost_price'], 'binding_version' => $snapshot['binding_version'], 'created_at' => $now, 'updated_at' => $now]);
            }
            $task = $this->tasks->enqueue(new TaskSpecVo($connection, 'order_push', 'purchase:' . $purchase['id'], ['purchase_id' => (int) $purchase['id']]));
            $this->repository->update('orders', (int) $purchase['id'], ['task_id' => $task]);
            $ids[] = $task;
        }
        return $ids;
    }

    public function submitting(int $purchaseId): array
    {
        $purchase = $this->repository->require('orders', $purchaseId, true);
        $order = $this->orders->find((int) $purchase['order_id']) ?? throw SupplyChainError::SALES_ORDER_NOT_FOUND->internal();
        $entity = PurchaseMapper::fromModel($purchase);
        $entity->submit($order);
        if ($this->repository->query('after_sales')->where('supply_order_id', $purchaseId)->whereNotIn('status', ['closed', 'rejected'])->exists()) {
            throw SupplyChainError::PURCHASE_PAUSED_FOR_AFTER_SALE->exception();
        }
        $this->repository->update('orders', $purchaseId, $entity->toArray());
        return $purchase;
    }

    public function accept(int $purchaseId, array $externalOrders): void
    {
        $purchase = $this->repository->require('orders', $purchaseId, true);
        $entity = PurchaseMapper::fromModel($purchase);
        $entity->accepted($externalOrders);
        $this->repository->update('orders', $purchaseId, $entity->toArray());
    }

    public function acknowledge(int $purchaseId): void
    {
        $purchase = $this->repository->require('orders', $purchaseId);
        $items = array_map(static fn ($row) => ['sku_id' => (int) $row['sku_id'], 'quantity' => (int) $row['quantity']], $purchase['snapshot']['items']);
        $this->inventory->acknowledge('supply_chain', $purchase['order_no'], $items);
    }

    public function assertCompletable(int $purchaseId): void
    {
        $purchase = $this->repository->require('orders', $purchaseId);
        $order = $this->orders->find((int) $purchase['order_id']) ?? throw SupplyChainError::SALES_ORDER_NOT_FOUND->exception();
        if (($order['status'] ?? '') !== 'completed') {
            throw SupplyChainError::SALES_ORDER_NOT_COMPLETED->exception();
        }
        $entity = PurchaseMapper::fromModel($purchase);
        $entity->complete();
    }

    public function complete(int $purchaseId): void
    {
        $entity = $this->getEntity($purchaseId, true);
        $entity->complete();
        $this->repository->update('orders', $purchaseId, $entity->toArray());
    }

    public function protect(int $purchaseId): void
    {
        $purchase = $this->repository->require('orders', $purchaseId);
        $items = array_map(static fn ($row) => ['sku_id' => (int) $row['sku_id'], 'quantity' => (int) $row['quantity']], $purchase['snapshot']['items']);
        $this->inventory->protect('supply_chain', $purchase['order_no'], $items);
    }

    /**
     * Persist the failure and its local compensation together. A Redis outage must not
     * turn a definite provider rejection into an ambiguous provider write.
     */
    public function failure(int $purchaseId, \Throwable $error, array $sourceTask): ?int
    {
        $purchase = $this->repository->require('orders', $purchaseId, true);
        $known = $error instanceof \DomainException || $error instanceof \InvalidArgumentException;
        $notSent = \in_array($error->getCode(), [429, 10002], true);
        $reason = SafeLogVo::error($error);
        $entity = PurchaseMapper::fromModel($purchase);
        $entity->failed($reason, $known, $notSent);
        $this->repository->update('orders', $purchaseId, $entity->toArray());
        if (! $known && ! $notSent) {
            return null;
        }
        $sourceTaskId = (int) $sourceTask['id'];
        $sourceAttemptId = (int) $sourceTask['attempt_id'];
        if ($sourceTaskId < 1 || $sourceAttemptId < 1 || (int) $sourceTask['connection_id'] !== (int) $purchase['connection_id']) {
            throw SupplyChainError::STOCK_GUARD_ATTEMPT_ID_MISSING->invalid();
        }
        $cleanupId = $this->tasks->enqueue(new TaskSpecVo(
            (int) $purchase['connection_id'],
            'order_release_protection',
            'purchase-release:' . $purchaseId . ':' . $sourceTaskId . ':' . $sourceAttemptId,
            ['purchase_id' => $purchaseId, 'source_task_id' => $sourceTaskId, 'source_attempt_id' => $sourceAttemptId,
                'reason' => $reason]
        ));
        $this->tasks->event($sourceTaskId, 'protection_release_queued', ['cleanup_task_id' => $cleanupId, 'reason' => $reason]);
        return $cleanupId;
    }

    /** Caller holds a short local transaction through the Redis Lua operation. No supplier HTTP. */
    public function releaseProtection(int $cleanupTaskId): array
    {
        $task = $this->repository->require('tasks', $cleanupTaskId);
        if ($task['type'] !== 'order_release_protection') {
            throw SupplyChainError::STOCK_GUARD_TASK_TYPE_INVALID->exception();
        }
        $purchaseId = (int) ($task['payload']['purchase_id'] ?? 0);
        $purchase = $this->repository->require('orders', $purchaseId, true);
        if ((int) $purchase['connection_id'] !== (int) $task['connection_id']) {
            throw SupplyChainError::STOCK_GUARD_CONNECTION_MISMATCH->exception();
        }
        // submitting() takes this same purchase lock before protecting a subsequent request.
        // Keep the lock until Lua finishes, so an old cleanup cannot remove a newer protection.
        if (! \in_array($purchase['status'], ['pending', 'rejected', 'cancelled'], true)) {
            $result = ['purchase_id' => $purchaseId, 'skipped' => 'purchase_state_' . $purchase['status']];
            $this->tasks->event($cleanupTaskId, 'protection_release_skipped', $result);
            return $result;
        }
        $items = array_map(static fn ($row) => ['sku_id' => (int) $row['sku_id'], 'quantity' => (int) $row['quantity']], $purchase['snapshot']['items']);
        $this->inventory->clearProtection('supply_chain', $purchase['order_no'], $items);
        $result = ['purchase_id' => $purchaseId, 'released' => true];
        $this->tasks->event($cleanupTaskId, 'protection_released', $result);
        return $result;
    }

    public function recordProtectionReleaseFailure(int $cleanupTaskId, \Throwable $error): void
    {
        $this->tasks->event($cleanupTaskId, 'protection_release_deferred', ['reason' => SafeLogVo::error($error)]);
    }

    public function queryTask(int $purchaseId, int $operator = 0): int
    {
        $purchase = $this->repository->require('orders', $purchaseId);
        return $this->tasks->enqueue(new TaskSpecVo((int) $purchase['connection_id'], 'order_query', 'order-query:' . $purchaseId . ':' . date('YmdHi'), ['purchase_id' => $purchaseId], $operator));
    }

    public function notify(string $orderNo, string $type): void
    {
        foreach ($this->repository->query('orders')->where('order_no', $orderNo)->get() as $raw) {
            $purchase = $this->repository->require('orders', (int) $raw->id, true);
            if ($type === 'order_cancel') {
                $entity = PurchaseMapper::fromModel($purchase);
                if ($entity->cancelBeforeSubmission()) {
                    $this->repository->update('orders', (int) $purchase['id'], $entity->toArray());
                    continue;
                }
            }
            $this->tasks->enqueue(new TaskSpecVo((int) $purchase['connection_id'], $type, $type . ':' . $purchase['id'], ['purchase_id' => (int) $purchase['id']]));
        }
    }

    public function applyShipment(int $purchaseId, string $externalOrderNo, array $package): void
    {
        $purchase = $this->repository->require('orders', $purchaseId);
        $localItems = $this->repository->query('order_items')->where('supply_order_id', $purchaseId)->get();
        $bySku = [];
        foreach ($localItems as $line) {
            $bySku[$line->external_sku_id][] = $line;
        }
        $items = [];
        foreach ($package['items'] as $line) {
            $matches = $bySku[$line['external_sku_id']] ?? [];
            if (\count($matches) !== 1 || (int) $line['quantity'] < 1) {
                throw SupplyChainError::SHIPMENT_ORDER_ITEM_AMBIGUOUS->exception();
            }
            $items[] = ['order_item_id' => (int) $matches[0]->order_item_id, 'quantity' => (int) $line['quantity']];
        }
        if ($items === []) {
            throw SupplyChainError::SHIPMENT_QUANTITY_MISSING->exception();
        }
        $key = hash('sha256', $purchaseId . ':' . $externalOrderNo . ':' . $package['company_code'] . ':' . $package['tracking_no']);
        $this->orders->ship((int) $purchase['order_id'], 'supply_chain', $key, ['shipping_company' => $package['company_code'] ?: $package['company_name'], 'shipping_no' => $package['tracking_no']], $items);
        $this->repository->query('shipments')->insertOrIgnore($this->repository->encode(['supply_order_id' => $purchaseId, 'shipment_key' => $key, 'shipping_company' => $package['company_code'] ?: $package['company_name'], 'shipping_no' => $package['tracking_no'], 'items' => $items, 'status' => 'applied', 'snapshot' => $package, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]));
    }
}
