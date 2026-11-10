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

namespace Plugin\Distribution\Domain\Entity;

final class CommissionAccountEntity
{
    private array $dirty = [];

    public function __construct(private array $state) {}

    public function state(): array
    {
        return $this->state;
    }

    public function toArray(): array
    {
        return array_intersect_key($this->state, $this->dirty);
    }

    public function pending(int $amount): void
    {
        $this->positive($amount);
        $this->add('pending_amount', $amount);
    }

    public function settle(int $amount): void
    {
        $this->positive($amount);
        $this->add('pending_amount', -$amount);
        $this->credit($amount);
    }

    public function reverse(int $amount, bool $settled): void
    {
        $this->positive($amount);
        if (! $settled) {
            $this->add('pending_amount', -$amount);
            return;
        }
        $deduct = min((int) $this->state['available_amount'], $amount);
        $this->add('available_amount', -$deduct);
        $this->add('debt_amount', $amount - $deduct);
    }

    public function freeze(int $amount): void
    {
        $this->positive($amount);
        if ($this->state['debt_amount'] > 0) {
            throw new \DomainException('存在待追回佣金，暂不能提现');
        }
        $this->add('available_amount', -$amount);
        $this->add('frozen_amount', $amount);
    }

    public function release(int $amount): void
    {
        $this->positive($amount);
        $this->add('frozen_amount', -$amount);
        $this->credit($amount);
    }

    public function pay(int $amount): void
    {
        if ($this->state['debt_amount'] > 0) {
            throw new \DomainException('退款形成待追回金额，须先处理后再确认打款');
        }
        $this->positive($amount);
        $this->add('frozen_amount', -$amount);
        $this->add('withdrawn_amount', $amount);
    }

    private function set(string $field, int $amount): void
    {
        if ($amount < 0) {
            throw new \DomainException('佣金余额不足或账目不一致');
        }
        $this->state[$field] = $amount;
        $this->dirty[$field] = true;
    }

    private function add(string $field, int $amount): void
    {
        $this->set($field, (int) $this->state[$field] + $amount);
    }

    private function credit(int $amount): void
    {
        $offset = min((int) $this->state['debt_amount'], $amount);
        $this->add('debt_amount', -$offset);
        $this->add('available_amount', $amount - $offset);
    }

    private function positive(int $amount): void
    {
        if ($amount < 0) {
            throw new \DomainException('金额不能为负');
        }
    }
}
