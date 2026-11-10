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

use Plugin\Distribution\Domain\Contract\WithdrawalInput;
use Plugin\Distribution\Domain\Contract\WithdrawalReviewInput;

final class WithdrawalEntity
{
    private array $dirty = [];

    public function __construct(private array $state = []) {}

    public function create(WithdrawalInput $input, int $memberId, array $policy): void
    {
        $data = $input->toArray();
        $amount = (int) $data['amount'];
        if ($amount < $policy['withdrawal_min_amount']) {
            throw new \DomainException('未达到最低提现金额');
        }
        $fee = intdiv($amount * (int) $policy['withdrawal_fee_bps'], 10000);
        $this->state = ['withdrawal_no' => 'WD' . date('YmdHis') . bin2hex(random_bytes(8)), 'request_key' => $data['request_key'] ?? null, 'member_id' => $memberId, 'amount' => $amount, 'fee_amount' => $fee, 'net_amount' => $amount - $fee, 'channel' => 'manual', 'account_name' => $data['account_name'], 'account_no' => $data['account_no'], 'remark' => $data['remark'] ?? '', 'status' => 'pending'];
        $this->dirty = array_fill_keys(array_keys($this->state), true);
    }

    public function review(WithdrawalReviewInput $input, int $operator): void
    {
        $data = $input->toArray();
        $action = $data['action'];
        $current = $this->state['status'];
        $allowed = ['approve' => ['pending'], 'reject' => ['pending', 'approved'], 'pay' => ['approved'], 'fail' => ['approved']];
        if (! \in_array($current, $allowed[$action] ?? [], true)) {
            throw new \DomainException('提现状态已变化，请刷新后操作');
        }
        if ($action === 'pay' && trim($data['payment_no'] ?? '') === '') {
            throw new \DomainException('确认打款须填写唯一付款流水号');
        }
        if (\in_array($action, ['reject', 'fail'], true) && trim($data['remark'] ?? '') === '') {
            throw new \DomainException('驳回或失败须填写原因');
        }
        $changes = ['status' => ['approve' => 'approved', 'reject' => 'rejected', 'pay' => 'paid', 'fail' => 'failed'][$action], 'operator_id' => $operator, 'remark' => $data['remark'] ?? ''];
        if ($action === 'pay') {
            $changes += ['payment_no' => $data['payment_no'], 'paid_at' => date('Y-m-d H:i:s')];
        }
        foreach ($changes as $key => $value) {
            $this->state[$key] = $value;
            $this->dirty[$key] = true;
        }
    }

    public function toArray(): array
    {
        return array_intersect_key($this->state, $this->dirty);
    }

    public function state(): array
    {
        return $this->state;
    }
}
