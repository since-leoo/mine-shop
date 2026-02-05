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

namespace App\Domain\Member\Repository;

use App\Domain\Member\Entity\MemberWalletEntity;
use App\Infrastructure\Model\Member\MemberWallet;

final class MemberWalletRepository
{
    public function __construct(
        private readonly MemberWallet $model,
    ) {}

    /**
     * 保存会员钱包.
     */
    public function save(MemberWalletEntity $wallet): void
    {
        /** @var MemberWallet $info */
        $info = $this->model::where('member_id', $wallet->getMemberId())->lockForUpdate()->first();

        $info?->fill($wallet->toArray())->save();
    }

    /**
     * 获取会员钱包.
     */
    public function findEntity(int $memberId, string $type): MemberWalletEntity
    {
        $wallet = $this->model->newQuery()
            ->where('member_id', $memberId)
            ->where('type', $type)
            ->first();

        $entity = new MemberWalletEntity();
        $entity->setMemberId($memberId);
        $entity->setType($type);

        if ($wallet) {
            $entity->setId($wallet->id);
            $entity->setBalance((float) $wallet->balance);
            $entity->setFrozenBalance((float) $wallet->frozen_balance);
            $entity->setTotalConsume((float) $wallet->total_consume);
            $entity->setTotalRecharge((float) $wallet->total_recharge);
            $entity->setStatus($wallet->status);
        }

        return $entity;
    }
}
