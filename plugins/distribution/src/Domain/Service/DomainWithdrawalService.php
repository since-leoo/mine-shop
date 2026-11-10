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

namespace Plugin\Distribution\Domain\Service;

use Plugin\Distribution\Domain\Contract\WithdrawalInput;
use Plugin\Distribution\Domain\Contract\WithdrawalReviewInput;
use Plugin\Distribution\Domain\Entity\WithdrawalEntity;
use Plugin\Distribution\Domain\Mapper\CommissionAccountMapper;
use Plugin\Distribution\Domain\Mapper\WithdrawalMapper;
use Plugin\Distribution\Domain\Repository\DistributionRepository;

final class DomainWithdrawalService
{
    public function __construct(private readonly DistributionRepository $repository) {}

    public function apply(int $memberId, WithdrawalInput $input): array
    {
        $member = $this->repository->member($memberId, true);
        if (! $member || $member['status'] !== 'active') {
            throw new \DomainException('当前会员不可申请提现');
        }
        $account = CommissionAccountMapper::fromModel($this->repository->account($memberId, true));
        $key = $input->toArray()['request_key'] ?? null;
        if ($key && ($existing = $this->repository->withdrawalByKey($memberId, $key))) {
            if ((int) $existing['amount'] !== (int) $input->toArray()['amount'] || $existing['account_no'] !== $input->toArray()['account_no']) {
                throw new \DomainException('重复申请标识对应不同提现内容');
            }
            return $existing;
        }
        if ($this->repository->hasOpenWithdrawal($memberId)) {
            throw new \DomainException('已有进行中的提现申请，请先等待处理');
        }
        $entity = WithdrawalMapper::getNewEntity();
        $entity->create($input, $memberId, $this->repository->settings()['settings']);
        $account->freeze((int) $entity->state()['amount']);
        $id = $this->repository->saveWithdrawal(null, $entity->toArray());
        $this->repository->saveAccount($account, 'withdraw:' . $id, 'withdrawal_freeze', -(int) $entity->state()['amount']);
        return $this->repository->withdrawal($id);
    }

    public function getEntity(int $id): WithdrawalEntity
    {
        $row = $this->repository->withdrawal($id);
        if (! $row) {
            throw new \RuntimeException('提现申请不存在');
        }
        return WithdrawalMapper::fromModel($row);
    }

    public function review(int $id, WithdrawalReviewInput $input, int $operator): array
    {
        $snapshot = $this->repository->withdrawal($id);
        if (! $snapshot) {
            throw new \RuntimeException('提现申请不存在');
        }
        // All money paths serialize on account before a withdrawal lock.
        $account = CommissionAccountMapper::fromModel($this->repository->account((int) $snapshot['member_id'], true));
        $row = $this->repository->withdrawal($id, true);
        $entity = WithdrawalMapper::fromModel($row);
        $action = $input->toArray()['action'];
        if ($action === 'pay' && $row['status'] === 'paid' && $row['payment_no'] === ($input->toArray()['payment_no'] ?? null)) {
            return $row;
        }
        $entity->review($input, $operator);
        if ($action === 'pay') {
            $account->pay((int) $row['amount']);
        } elseif (\in_array($action, ['reject', 'fail'], true)) {
            $account->release((int) $row['amount']);
        }
        $this->repository->saveWithdrawal($id, $entity->toArray());
        if ($action !== 'approve') {
            $this->repository->saveAccount($account, 'withdraw:' . $id . ':' . $action, 'withdrawal_' . $action, (int) $row['amount']);
        }
        $this->repository->audit($operator, 'withdrawal.' . $action, $id, ['status' => $entity->state()['status'], 'remark' => $input->toArray()['remark'] ?? '']);
        return $this->repository->withdrawal($id);
    }
}
