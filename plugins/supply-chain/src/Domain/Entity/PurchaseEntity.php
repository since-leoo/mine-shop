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

final class PurchaseEntity
{
    private array $dirty = [];

    public function __construct(private array $state) {}

    public function submit(array $order): void
    {
        if (! \in_array($this->state['status'], ['pending', 'rejected'], true)) {
            throw new \DomainException('采购已经提交或结果待确认，请先查单');
        }
        if (($order['pay_status'] ?? '') !== 'paid' || \in_array($order['status'] ?? '', ['cancelled', 'closed', 'refunded'], true)) {
            throw new \DomainException('销售订单已取消、退款或未支付，不能采购');
        }
        $this->change(['status' => 'submitting', 'last_error' => null]);
    }

    public function accepted(array $orders): void
    {
        if ($orders === []) {
            throw new \DomainException('不能确认没有上游订单的采购');
        }
        $this->change(['status' => $this->state['status'] === 'completed' ? 'completed' : 'accepted', 'external_orders' => $orders, 'last_error' => null]);
    }

    public function complete(): void
    {
        if (! \in_array($this->state['status'], ['accepted', 'completed'], true)) {
            throw new \DomainException('采购尚未确认受理，不能完成');
        }
        $this->change(['status' => 'completed', 'last_error' => null]);
    }

    public function failed(string $reason, bool $known, bool $notSent = false): void
    {
        if (\in_array($this->state['status'], ['accepted', 'completed', 'cancelled'], true)) {
            return;
        }
        $this->change(['status' => $notSent ? 'pending' : ($known ? 'rejected' : 'unknown'), 'last_error' => $reason]);
    }

    public function cancelBeforeSubmission(): bool
    {
        if ($this->state['status'] !== 'pending') {
            return false;
        }
        $this->change(['status' => 'cancelled']);
        return true;
    }

    public function toArray(): array
    {
        return $this->dirty;
    }

    private function change(array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->state = array_replace($this->state, $data);
        $this->dirty = array_replace($this->dirty, $data);
    }
}
