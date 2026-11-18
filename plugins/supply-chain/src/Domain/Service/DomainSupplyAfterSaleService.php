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

use App\Domain\Infrastructure\Plugin\Contract\CommerceAfterSaleInterface;
use App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface;
use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Domain\Entity\SupplyAfterSaleEntity;
use Plugin\SupplyChain\Domain\Mapper\SupplyAfterSaleMapper;
use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\AfterSaleSubmissionVo;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Domain\ValueObject\TaskSpecVo;

/** Procurement refunds are separate from the customer's money and approval lifecycle. */
final class DomainSupplyAfterSaleService
{
    public function __construct(
        private readonly SupplyRepository $repository,
        private readonly DomainSupplyTaskService $tasks,
        private readonly CommerceAfterSaleInterface $customerAfterSales,
        private readonly CommerceOrderInterface $customerOrders,
    ) {}

    /** Local persistence only; the reliable event consumer owns the transaction. */
    public function changed(int $afterSaleId, string $action): void
    {
        $customer = $this->customerAfterSales->find($afterSaleId);
        if ($customer === null || ($customer['order_item']['fulfillment_provider'] ?? 'merchant') !== 'supply_chain') {
            return;
        }
        $orderItemId = (int) $customer['order_item_id'];
        $matches = $this->repository->query('order_items')->where('order_item_id', $orderItemId)->get()->all();
        if ($matches === []) {
            $order = $this->customerOrders->find((int) $customer['order_id']);
            if ($order === null || \in_array($order['status'] ?? '', ['cancelled', 'closed', 'refunded'], true)) {
                return;
            }
            throw SupplyChainError::PURCHASE_NOT_PERSISTED->internal();
        }
        if (\count($matches) !== 1) {
            throw SupplyChainError::AFTER_SALE_MULTIPLE_PURCHASE_LINES->exception();
        }
        $purchase = $this->repository->require('orders', (int) $matches[0]->supply_order_id, true);
        if ((int) $purchase['order_id'] !== (int) $customer['order_id']) {
            throw SupplyChainError::PURCHASE_ORDER_OWNER_MISMATCH->exception();
        }
        $row = $this->repository->decode($this->repository->query('after_sales')->where('after_sale_id', $afterSaleId)
            ->where('supply_order_id', $purchase['id'])->lockForUpdate()->first());
        $entity = $row === null ? SupplyAfterSaleMapper::getNewEntity() : SupplyAfterSaleMapper::fromModel($row);
        if ($row === null) {
            $entity->create((int) $purchase['connection_id'], $afterSaleId, (int) $purchase['id'], $this->customerSnapshot($customer));
        }
        $entity->customerChanged($this->customerSnapshot($customer), $action);
        $id = $row === null ? $this->repository->insert('after_sales', $entity->toArray()) : (int) $row['id'];
        if ($row !== null) {
            $this->repository->update('after_sales', $id, $entity->toArray());
        }
        $this->scheduleFollowUp($id, $entity);
    }

    public function getEntity(int $id, bool $lock = false): SupplyAfterSaleEntity
    {
        return SupplyAfterSaleMapper::fromModel($this->repository->require('after_sales', $id, $lock));
    }

    /** Read-only validation before asking the provider for this exact line's allowed types. */
    public function previewSubmission(int $id, array $input): array
    {
        [$row, , $submission] = $this->validateSubmission($id, $input, false);
        return ['connection_id' => (int) $row['connection_id'], 'external_order_item_id' => $submission->data['external_order_item_id']];
    }

    /** Revalidates and reserves budgets under a purchase lock after the remote policy read. */
    public function submit(int $id, array $input, array $types, int $actor): array
    {
        [$row, $remoteLine, $submission] = $this->validateSubmission($id, $input, true);
        $submission->assertTypes($types);
        $entity = SupplyAfterSaleMapper::fromModel($row);
        $revision = $entity->queueSubmission($submission, $remoteLine, $types);
        $this->repository->update('after_sales', $id, $entity->toArray());
        return ['task_id' => $this->enqueue($id, $entity, 'after_sale_create', 'create:' . $revision, $actor)];
    }

    public function query(int $id, int $actor = 0): array
    {
        $entity = $this->getEntity($id);
        $row = $entity->snapshot();
        if (empty($row['external_after_sale_id']) && empty($row['snapshot']['submission']['external_order_item_id'])) {
            throw SupplyChainError::AFTER_SALE_QUERY_TARGET_MISSING->exception();
        }
        return ['task_id' => $this->enqueue($id, $entity, 'after_sale_query', 'query:' . bin2hex(random_bytes(8)), $actor)];
    }

    /** Acquires a short local write lease and returns immutable HTTP arguments. */
    public function start(array $task): array
    {
        $this->assertTaskOwner($task);
        $id = (int) ($task['payload']['supply_after_sale_id'] ?? 0);
        $row = $this->repository->require('after_sales', $id);
        $purchase = $this->repository->require('orders', (int) $row['supply_order_id'], true);
        $entity = $this->getEntity($id, true);
        $row = $entity->snapshot();
        if ((int) $row['connection_id'] !== (int) $task['connection_id']) {
            throw SupplyChainError::AFTER_SALE_CONNECTION_MISMATCH->exception();
        }
        $action = (string) $task['type'];
        $revision = (int) ($task['payload']['revision'] ?? -1);
        $context = ['connection_id' => (int) $row['connection_id'], 'external_after_sale_id' => $row['external_after_sale_id'],
            'external_order_item_id' => $row['snapshot']['submission']['external_order_item_id'] ?? null];
        if ($action === 'after_sale_create') {
            $customer = $this->requireCustomer($row, $purchase);
            $submission = new AfterSaleSubmissionVo($row['snapshot']['submission'] ?? []);
            $line = $row['snapshot']['remote_line'] ?? [];
            $submission->assertLimits($customer, $line, $this->usedBudgets($row, $line), (int) $line['freight_cents']);
            $context['command'] = $submission->toCommand();
        } elseif ($action === 'after_sale_return') {
            $context['return_shipment'] = $this->returnShipment($row);
        } elseif ($action === 'after_sale_query') {
            if (empty($context['external_after_sale_id']) && empty($context['external_order_item_id'])) {
                throw SupplyChainError::AFTER_SALE_LOOKUP_ID_MISSING->exception();
            }
        } elseif ($action !== 'after_sale_cancel') {
            throw SupplyChainError::UNKNOWN_AFTER_SALE_TASK->exception();
        }
        if (\in_array($action, ['after_sale_cancel', 'after_sale_return'], true) && empty($context['external_after_sale_id'])) {
            throw SupplyChainError::AFTER_SALE_CONFIRMED_ID_MISSING->exception();
        }
        $entity->begin($action, $revision);
        $this->repository->update('after_sales', $id, $entity->toArray());
        return $context;
    }

    public function finish(array $task, mixed $result): array
    {
        $this->assertTaskOwner($task);
        $id = (int) $task['payload']['supply_after_sale_id'];
        $row = $this->repository->require('after_sales', $id);
        $this->repository->require('orders', (int) $row['supply_order_id'], true);
        $entity = $this->getEntity($id, true);
        $row = $entity->snapshot();
        if ((int) ($row['snapshot']['revision'] ?? 0) !== (int) $task['payload']['revision']) {
            throw new \RuntimeException(SupplyChainError::AFTER_SALE_RESULT_VERSION_EXPIRED->message(), 10001);
        }
        if ($task['type'] === 'after_sale_create' && \is_string($result) && $result !== '') {
            if ($this->repository->query('after_sales')->where('connection_id', $row['connection_id'])
                ->where('external_after_sale_id', $result)->where('id', '!=', $id)->exists()) {
                throw new \RuntimeException(SupplyChainError::AFTER_SALE_ID_ALREADY_BOUND->message(), 10001);
            }
        }
        if ($task['type'] === 'after_sale_query' && ! empty($row['external_after_sale_id'])
            && ! empty($result['external_after_sale_id']) && $row['external_after_sale_id'] !== (string) $result['external_after_sale_id']) {
            throw SupplyChainError::AFTER_SALE_QUERY_ID_MISMATCH->exception();
        }
        $entity->succeeded((string) $task['type'], $result);
        $this->repository->update('after_sales', $id, $entity->toArray());
        // A customer cancellation/return may arrive while create is in flight.
        if ($task['type'] !== 'after_sale_query') {
            $this->scheduleFollowUp($id, $entity);
        }
        return ['supply_after_sale_id' => $id, 'status' => $entity->snapshot()['status'],
            'external_after_sale_id' => $entity->snapshot()['external_after_sale_id']];
    }

    public function failure(array $task, \Throwable $error): void
    {
        try {
            $this->assertTaskOwner($task);
        } catch (\RuntimeException) {
            return;
        }
        $id = (int) ($task['payload']['supply_after_sale_id'] ?? 0);
        if ($this->repository->find('after_sales', $id) === null) {
            return;
        }
        $entity = $this->getEntity($id, true);
        $notSent = \in_array($error->getCode(), [429, 10002], true);
        $unknown = ! $notSent && ! $error instanceof \DomainException && ! $error instanceof \InvalidArgumentException;
        $entity->failed((string) $task['type'], SafeLogVo::error($error), $unknown, $notSent);
        $this->repository->update('after_sales', $id, $entity->toArray());
        if ($unknown && $task['type'] !== 'after_sale_query') {
            $this->enqueue($id, $entity, 'after_sale_query', 'reconcile:' . $task['id'] . ':' . $task['attempt_count']);
        }
    }

    private function validateSubmission(int $id, array $input, bool $lock): array
    {
        $row = $this->repository->require('after_sales', $id);
        $purchase = $this->repository->require('orders', (int) $row['supply_order_id'], $lock);
        if ($lock) {
            $row = $this->repository->require('after_sales', $id, true);
        }
        if ($row['status'] !== 'pending_review' || ! empty($row['snapshot']['cancel_requested'])) {
            throw SupplyChainError::AFTER_SALE_ALREADY_SUBMITTED->exception();
        }
        if (! \in_array($purchase['status'], ['accepted', 'completed'], true)) {
            throw SupplyChainError::PURCHASE_LINES_UNCONFIRMED->exception();
        }
        $customer = $this->requireCustomer($row, $purchase);
        $localLines = $this->repository->query('order_items')->where('supply_order_id', $purchase['id'])->get()->all();
        $local = array_values(array_filter($localLines, static fn ($line) => (int) $line->order_item_id === (int) $customer['order_item_id']));
        if (\count($local) !== 1) {
            throw SupplyChainError::AFTER_SALE_PURCHASE_LINE_AMBIGUOUS->exception();
        }
        $sameSku = array_filter($localLines, static fn ($line) => (string) $line->external_sku_id === (string) $local[0]->external_sku_id);
        if (\count($sameSku) !== 1) {
            throw SupplyChainError::AFTER_SALE_MERGED_SKU_AMBIGUOUS->exception();
        }
        $orders = $purchase['external_orders']['orders'] ?? $purchase['external_orders'] ?? [];
        $matches = [];
        foreach ($orders as $order) {
            foreach ($order['items'] ?? [] as $line) {
                if ((string) ($line['external_sku_id'] ?? '') === (string) $local[0]->external_sku_id) {
                    $matches[] = $line + ['external_order_no' => $order['external_order_no'] ?? '', 'freight_cents' => $order['freight_cents'] ?? 0];
                }
            }
        }
        if (\count($matches) !== 1) {
            throw SupplyChainError::AFTER_SALE_SPLIT_SKU_AMBIGUOUS->exception();
        }
        $remote = $matches[0];
        if ((string) ($remote['external_order_item_id'] ?? '') !== (string) ($input['external_order_item_id'] ?? '')
            || (int) ($remote['quantity'] ?? 0) !== (int) $local[0]->quantity) {
            throw SupplyChainError::AFTER_SALE_FROZEN_LINE_MISMATCH->exception();
        }
        foreach (['amount_cents', 'technical_service_fee_cents', 'freight_cents'] as $field) {
            if (! isset($remote[$field]) || ! is_numeric($remote[$field]) || $remote[$field] < 0) {
                throw SupplyChainError::PURCHASE_LINE_AMOUNT_MISSING->exception();
            }
        }
        $data = array_intersect_key($input, array_flip(['external_order_item_id', 'quantity', 'amount_cents', 'freight_cents', 'technical_service_fee_cents', 'reason_type', 'reason', 'description', 'received_status', 'refund_type', 'refund_way']));
        $data += ['freight_cents' => 0, 'technical_service_fee_cents' => 0, 'description' => ''];
        $data['merchant_order_no'] = (string) $purchase['merchant_order_no'];
        $data['images'] = array_values(array_filter($customer['images'] ?? [], 'is_string'));
        $submission = new AfterSaleSubmissionVo($data);
        $submission->assertLimits($customer, $remote, $this->usedBudgets($row, $remote), (int) $remote['freight_cents']);
        return [$row, $remote, $submission];
    }

    private function requireCustomer(array $row, array $purchase): array
    {
        $customer = $this->customerAfterSales->find((int) $row['after_sale_id']) ?? throw SupplyChainError::CUSTOMER_AFTER_SALE_NOT_FOUND->exception();
        if ((int) $customer['order_id'] !== (int) $purchase['order_id'] || ($customer['order_item']['fulfillment_provider'] ?? '') !== 'supply_chain') {
            throw SupplyChainError::AFTER_SALE_PURCHASE_OWNER_MISMATCH->exception();
        }
        if (($customer['status'] ?? '') === 'closed') {
            throw SupplyChainError::CUSTOMER_AFTER_SALE_CLOSED->exception();
        }
        return $customer;
    }

    private function usedBudgets(array $row, array $remote): array
    {
        $used = ['quantity' => 0, 'amount_cents' => 0, 'technical_service_fee_cents' => 0, 'freight_cents' => 0];
        foreach ($this->repository->query('after_sales')->where('supply_order_id', $row['supply_order_id'])->where('id', '!=', $row['id'])->get() as $raw) {
            $other = $this->repository->decode($raw);
            if (\in_array($other['status'], ['pending_review', 'closed', 'rejected'], true)) {
                continue;
            }
            $submission = $other['snapshot']['submission'] ?? [];
            $line = $other['snapshot']['remote_line'] ?? [];
            if (($line['external_order_no'] ?? null) === $remote['external_order_no']) {
                $used['freight_cents'] += (int) ($submission['freight_cents'] ?? 0);
            }
            if (($submission['external_order_item_id'] ?? null) !== (string) $remote['external_order_item_id']) {
                continue;
            }
            foreach (['quantity', 'amount_cents', 'technical_service_fee_cents'] as $field) {
                $used[$field] += (int) ($submission[$field] ?? 0);
            }
        }
        return $used;
    }

    private function scheduleFollowUp(int $id, SupplyAfterSaleEntity $entity): void
    {
        $row = $entity->snapshot();
        if (! empty($row['snapshot']['cancel_requested'])) {
            if ($entity->queueAction('after_sale_cancel')) {
                $this->repository->update('after_sales', $id, $entity->toArray());
                $this->enqueue($id, $entity, 'after_sale_cancel', 'cancel');
            } elseif (\in_array($row['status'], ['submitting', 'unknown'], true)) {
                $this->enqueue($id, $entity, 'after_sale_query', 'cancel-reconcile');
            }
            return;
        }
        $customer = $row['snapshot']['customer'];
        if (! empty($customer['buyer_return_logistics_no']) && ! empty($customer['buyer_return_logistics_company'])
            && $entity->queueAction('after_sale_return', ['company' => $customer['buyer_return_logistics_company'], 'tracking_no' => $customer['buyer_return_logistics_no']])) {
            $this->repository->update('after_sales', $id, $entity->toArray());
            $this->enqueue($id, $entity, 'after_sale_return', 'return:' . hash('sha256', $customer['buyer_return_logistics_no']));
        }
    }

    private function returnShipment(array $row): array
    {
        $shipment = $row['snapshot']['return_shipment'] ?? [];
        $connection = $this->repository->require('connections', (int) $row['connection_id']);
        $carrier = $connection['settings']['return_carriers'][$shipment['company'] ?? ''] ?? null;
        if (! \is_array($carrier) || empty($carrier['name']) || empty($carrier['code']) || empty($shipment['tracking_no'])) {
            throw SupplyChainError::RETURN_CARRIER_MAPPING_MISSING->exception();
        }
        return ['company_name' => (string) $carrier['name'], 'company_code' => (string) $carrier['code'], 'tracking_no' => (string) $shipment['tracking_no']];
    }

    private function assertTaskOwner(array $task): void
    {
        $current = $this->repository->require('tasks', (int) $task['id'], true);
        if ($current['status'] !== 'running' || ! hash_equals((string) ($current['lease_token'] ?? ''), (string) ($task['lease_token'] ?? ''))
            || strtotime((string) ($current['lease_expires_at'] ?? '')) <= time()) {
            throw new \RuntimeException(SupplyChainError::AFTER_SALE_LEASE_EXPIRED->message(), 10001);
        }
    }

    private function enqueue(int $id, SupplyAfterSaleEntity $entity, string $type, string $suffix, int $actor = 0): int
    {
        $row = $entity->snapshot();
        return $this->tasks->enqueue(new TaskSpecVo(
            (int) $row['connection_id'],
            $type,
            'after-sale:' . $id . ':' . $suffix,
            ['supply_after_sale_id' => $id, 'revision' => (int) ($row['snapshot']['revision'] ?? 0)],
            $actor
        ));
    }

    private function customerSnapshot(array $customer): array
    {
        return array_intersect_key($customer, array_flip(['id', 'order_id', 'order_item_id', 'type', 'status', 'quantity', 'apply_amount', 'refund_amount', 'reason', 'description', 'images', 'buyer_return_logistics_company', 'buyer_return_logistics_no']));
    }
}
