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

use App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Domain\ValueObject\SupplyAddressVo;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;
use Plugin\SupplyChain\Interfaces\Data\PurchaseCommand;
use Plugin\SupplyChain\Interfaces\Data\PurchaseLine;

final class DomainSupplyWorkflowService
{
    public function __construct(private readonly SupplyRepository $repository, private readonly DomainSupplyTaskService $tasks, private readonly DomainSupplyProductService $products, private readonly DomainSupplyPurchaseService $purchases, private readonly CommerceOrderInterface $orders) {}

    public function connection(int $id, string $type): array
    {
        $row = $this->repository->require('connections', $id);
        if (! $row['enabled']) {
            throw new \DomainException('供应链连接已停用');
        }
        if ($type === 'import' && $row['mode'] !== 'active') {
            throw new \DomainException('连接已暂停新选品');
        }
        return $row;
    }

    public function purchase(int $id): array
    {
        return $this->repository->require('orders', $id);
    }

    public function assertTaskOwner(array $task): void
    {
        $current = $this->repository->require('tasks', (int) $task['id'], true);
        if ($current['status'] !== 'running' || ! hash_equals((string) $current['lease_token'], (string) $task['lease_token']) || strtotime($current['lease_expires_at'] ?? '1970-01-01') <= time()) {
            throw new \RuntimeException('任务租约已失效，禁止继续发送写请求', 10002);
        }
    }

    public function command(array $purchase): PurchaseCommand
    {
        $lines = array_map(static fn ($line) => new PurchaseLine($line['snapshot']['external_sku_id'], (int) $line['quantity']), $purchase['snapshot']['items']);
        return new PurchaseCommand($purchase['merchant_order_no'], $lines, SupplyAddressVo::fromArray($purchase['snapshot']['address']));
    }

    public function assertQuote(array $purchase, array $catalog, array $quote): void
    {
        $prices = [];
        foreach ($catalog as $product) {
            if (! $product['available']) {
                throw new \DomainException('采购前上游商品已停供');
            }
            foreach ($product['skus'] as $sku) {
                $prices[$sku['external_sku_id']] = $sku;
            }
        }
        $allowedFreight = 0;
        foreach ($purchase['snapshot']['items'] as $line) {
            $snapshot = $line['snapshot'];
            $sku = $prices[$snapshot['external_sku_id']] ?? null;
            if ($sku === null || (int) $sku['stock'] < (int) $line['quantity']) {
                throw new \DomainException('采购前校验上游库存不足');
            }
            if ((int) $sku['cost_price'] > (int) $snapshot['cost_price']) {
                throw new \DomainException('采购成本高于下单快照，需人工处理差额');
            }
            $allowedFreight = max($allowedFreight, (int) ($snapshot['freight_cents'] ?? 0));
        }
        if ((int) $quote['freight_cents'] > $allowedFreight) {
            throw new \DomainException('采购运费高于下单快照，需人工处理差额');
        }
    }

    public function productFailure(array $task, \Throwable $error): void
    {
        if (isset($task['payload']['link_id'])) {
            $this->repository->update('product_links', (int) $task['payload']['link_id'], ['last_error' => SafeLogVo::error($error), 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function receivedMessages(int $connectionId, array $messages): array
    {
        $ids = [];
        $now = date('Y-m-d H:i:s');
        foreach ($messages as $message) {
            $this->repository->query('messages')->insertOrIgnore($this->repository->encode(['connection_id' => $connectionId, 'external_message_id' => $message['external_message_id'], 'type' => $message['type'], 'payload' => $message['payload'], 'status' => 'received', 'created_at' => $now, 'updated_at' => $now]));
            $ids[] = $message['external_message_id'];
        }
        // Durably schedule reconciliation before ACK; message payload itself never authorizes writes.
        if ($ids !== []) {
            $this->tasks->enqueue(new TaskSpecVo($connectionId, 'connection_refresh', 'messages-refresh:' . $connectionId . ':' . hash('sha256', implode('|', $ids)), ['product_after' => 0, 'purchase_after' => 0]));
            $this->repository->query('messages')->where('connection_id', $connectionId)->whereIn('external_message_id', $ids)->update(['status' => 'processed', 'updated_at' => $now]);
        }
        return $ids;
    }

    public function acknowledgeMessages(int $connectionId, array $ids): void
    {
        $this->repository->query('messages')->where('connection_id', $connectionId)->whereIn('external_message_id', $ids)->update(['acknowledged' => true, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function refresh(array $task): array
    {
        $payload = $task['payload'];
        $connectionId = (int) $task['connection_id'];
        $links = $this->repository->query('product_links')->where('connection_id', $connectionId)->where('id', '>', (int) ($payload['product_after'] ?? 0))->orderBy('id')->limit(50)->get();
        $purchases = $this->repository->query('orders')->where('connection_id', $connectionId)->where('id', '>', (int) ($payload['purchase_after'] ?? 0))->whereIn('status', ['pending', 'submitting', 'unknown', 'accepted'])->orderBy('id')->limit(50)->get();
        $productAfter = (int) ($payload['product_after'] ?? 0);
        $purchaseAfter = (int) ($payload['purchase_after'] ?? 0);
        foreach ($links as $link) {
            $this->products->requestSync((int) $link->id, 0);
            $productAfter = (int) $link->id;
        }
        foreach ($purchases as $purchase) {
            // A pending order has never been submitted. Its original push task is the only submit path.
            if ($purchase->status !== 'pending') {
                $this->purchases->queryTask((int) $purchase->id);
            }
            $purchaseAfter = (int) $purchase->id;
        }
        if (\count($links) === 50 || \count($purchases) === 50) {
            $this->tasks->enqueue(new TaskSpecVo($connectionId, 'connection_refresh', 'refresh-page:' . $task['id'] . ':' . $productAfter . ':' . $purchaseAfter, ['product_after' => $productAfter, 'purchase_after' => $purchaseAfter]));
        }
        return ['products' => \count($links), 'purchases' => \count($purchases)];
    }

    public function schedule(): void
    {
        foreach ($this->repository->query('connections')->where('enabled', true)->get() as $connection) {
            $id = (int) $connection->id;
            $this->tasks->enqueue(new TaskSpecVo($id, 'messages', 'messages:' . $id . ':' . date('YmdHi'), []));
            $this->tasks->enqueue(new TaskSpecVo($id, 'connection_refresh', 'refresh:' . $id . ':' . intdiv(time(), 300), ['product_after' => 0, 'purchase_after' => 0]));
        }
    }

    public function paidRecoveryPage(int $after): array
    {
        $orders = $this->orders->paidSince('supply_chain', $after, 100);
        foreach ($orders as $order) {
            $this->purchases->ensure((int) $order['id']);
        }
        return ['last_id' => $orders === [] ? $after : (int) end($orders)['id'], 'count' => \count($orders)];
    }

    public function resolvePush(int $purchaseId): void
    {
        $query = $this->repository->query('tasks')->where('business_key', 'purchase:' . $purchaseId)->where('status', 'unknown');
        $task = $query->first();
        if ($task !== null) {
            $this->repository->update('tasks', (int) $task->id, ['status' => 'succeeded', 'result' => ['reconciled' => true, 'purchase_id' => $purchaseId], 'last_error' => null, 'updated_at' => date('Y-m-d H:i:s')]);
            $this->tasks->event((int) $task->id, 'reconciled', ['purchase_id' => $purchaseId]);
        }
    }
}
