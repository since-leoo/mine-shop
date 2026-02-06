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
use Hyperf\Database\Model\Model;

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
        /** @var null|MemberWallet $info */
        $info = $this->model::where('member_id', $wallet->getMemberId())->lockForUpdate()->first();

        $info?->fill($wallet->toArray())->save();
    }

    /**
     * 通过会员ID和类型查找钱包 Model.
     */
    public function findByMemberIdAndType(int $memberId, string $type): ?MemberWallet
    {
        return $this->model->newQuery()
            ->where('member_id', $memberId)
            ->where('type', $type)
            ->first();
    }

    /**
     * 创建或更新.
     */
    public function updateOrCreate(array $condition, array $data): Model
    {
        return $this->model::query()->updateOrCreate($condition, $data);
    }
}
