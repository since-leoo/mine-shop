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

namespace Plugin\SupplyChain\Domain\Entity;

use Plugin\SupplyChain\Domain\ValueObject\AfterSaleSubmissionVo;

/** Supplier refund lifecycle; it never transitions the customer's refund or payment. */
final class SupplyAfterSaleEntity
{
    private array $dirty = [];

    public function __construct(private array $state = []) {}

    public function create(int $connectionId, int $afterSaleId, int $purchaseId, array $customer): void
    {
        $this->change(['connection_id' => $connectionId, 'after_sale_id' => $afterSaleId,
            'supply_order_id' => $purchaseId, 'status' => 'pending_review', 'external_after_sale_id' => null,
            'snapshot' => ['schema_version' => 1, 'customer' => $customer, 'revision' => 0],
            'last_error' => null, 'created_at' => date('Y-m-d H:i:s')]);
    }

    public function customerChanged(array $customer, string $action): void
    {
        $snapshot = $this->state['snapshot'];
        $snapshot['customer'] = $customer;
        $snapshot['last_customer_action'] = $action;
        if ($action === 'cancel' || ($customer['status'] ?? '') === 'closed') {
            $snapshot['cancel_requested'] = true;
        }
        $this->change(['snapshot' => $snapshot]);
        if (! empty($snapshot['cancel_requested']) && \in_array($this->state['status'], ['pending_review', 'queued'], true)) {
            $this->change(['status' => 'closed']);
        }
    }

    public function queueSubmission(AfterSaleSubmissionVo $submission, array $remoteLine, array $types): int
    {
        if ($this->state['status'] !== 'pending_review') {
            throw new \DomainException('采购售后已排队、已提交或结果未知，不能重复申请；请先查询');
        }
        if (! empty($this->state['snapshot']['cancel_requested'])) {
            throw new \DomainException('客户已取消售后');
        }
        $snapshot = $this->state['snapshot'];
        $snapshot['revision'] = (int) ($snapshot['revision'] ?? 0) + 1;
        $snapshot['submission'] = $submission->data;
        $snapshot['remote_line'] = $remoteLine;
        $snapshot['refund_types'] = $types;
        unset($snapshot['candidate_external_after_sale_id']);
        $this->change(['status' => 'queued', 'snapshot' => $snapshot, 'last_error' => null]);
        return $snapshot['revision'];
    }

    public function queueAction(string $action, ?array $returnShipment = null): bool
    {
        if (empty($this->state['external_after_sale_id']) || \in_array($this->state['status'], ['unknown', 'submitting'], true)) {
            return false;
        }
        if ($action === 'after_sale_cancel') {
            if ($this->state['status'] === 'closed') {
                return false;
            }
            $this->change(['status' => 'cancel_queued']);
        } elseif ($action === 'after_sale_return') {
            if (\in_array($this->state['status'], ['closed', 'return_submitted', 'cancel_queued'], true)) {
                return false;
            }
            $snapshot = $this->state['snapshot'];
            $snapshot['return_shipment'] = $returnShipment;
            $this->change(['status' => 'return_queued', 'snapshot' => $snapshot]);
        }
        return true;
    }

    public function begin(string $action, int $revision): void
    {
        if ((int) ($this->state['snapshot']['revision'] ?? 0) !== $revision) {
            throw new \DomainException('采购售后任务已被新的提交版本替代');
        }
        if ($action === 'after_sale_query') {
            return;
        }
        $expected = match ($action) {
            'after_sale_create' => 'queued', 'after_sale_cancel' => 'cancel_queued',
            'after_sale_return' => 'return_queued', default => throw new \InvalidArgumentException('未知采购售后任务类型'),
        };
        if ($this->state['status'] !== $expected) {
            throw new \DomainException('采购售后当前状态禁止执行该写操作，结果未知必须先查单');
        }
        $snapshot = $this->state['snapshot'];
        $snapshot['executing_action'] = $action;
        $snapshot['last_submitted_at'] = date('Y-m-d H:i:s');
        $this->change(['status' => 'submitting', 'snapshot' => $snapshot]);
    }

    public function succeeded(string $action, mixed $result): void
    {
        if ($action === 'after_sale_query') {
            $snapshot = $this->state['snapshot'];
            $snapshot['upstream_observation'] = $result;
            if (empty($this->state['external_after_sale_id']) && ! empty($result['external_after_sale_id'])) {
                // No merchant after-sale idempotency key exists in the scanned API. A match by item alone is not proof.
                $snapshot['candidate_external_after_sale_id'] = (string) $result['external_after_sale_id'];
                $this->change(['snapshot' => $snapshot, 'status' => 'unknown',
                    'last_error' => '按采购行查到候选售后；需人工确认归属，禁止自动认领或重新提交']);
                return;
            }
            $this->change(['snapshot' => $snapshot]);
            return;
        }
        $changes = ['last_error' => null];
        if ($action === 'after_sale_create') {
            if (! \is_string($result) || $result === '') {
                throw new \RuntimeException('供应商未返回有效售后编号', 10001);
            }
            $changes += ['external_after_sale_id' => $result, 'status' => 'submitted'];
        } elseif ($action === 'after_sale_cancel') {
            $changes['status'] = 'closed';
        } elseif ($action === 'after_sale_return') {
            $changes['status'] = 'return_submitted';
        }
        $this->change($changes);
    }

    public function failed(string $action, string $reason, bool $unknown, bool $notSent): void
    {
        if ($action === 'after_sale_query') {
            $this->change(['last_error' => $reason]);
            return;
        }
        if ($this->state['status'] !== 'submitting') {
            $this->change(['last_error' => $reason]);
            return;
        }
        $status = $unknown ? 'unknown' : match ($action) {
            'after_sale_create' => $notSent ? 'queued' : 'pending_review',
            'after_sale_cancel' => 'cancel_queued',
            'after_sale_return' => 'return_queued',
            default => throw new \InvalidArgumentException('未知采购售后任务类型'),
        };
        $this->change(['status' => $status, 'last_error' => $reason]);
    }

    public function snapshot(): array
    {
        return $this->state;
    }

    public function toArray(): array
    {
        return $this->dirty;
    }

    private function change(array $fields): void
    {
        $fields['updated_at'] = date('Y-m-d H:i:s');
        $this->state = array_replace($this->state, $fields);
        $this->dirty = array_replace($this->dirty, $fields);
    }
}
