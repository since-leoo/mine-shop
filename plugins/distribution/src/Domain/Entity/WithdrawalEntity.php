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

use App\Infrastructure\Error\DistributionError;
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
            throw DistributionError::WITHDRAWAL_BELOW_MINIMUM->exception();
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
            throw DistributionError::WITHDRAWAL_STATE_CHANGED->exception();
        }
        if ($action === 'pay' && trim($data['payment_no'] ?? '') === '') {
            throw DistributionError::PAYMENT_REFERENCE_REQUIRED->exception();
        }
        if (\in_array($action, ['reject', 'fail'], true) && trim($data['remark'] ?? '') === '') {
            throw DistributionError::WITHDRAWAL_REJECTION_REASON_REQUIRED->exception();
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
