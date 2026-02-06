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

namespace App\Domain\Member\Mapper;

use App\Domain\Member\Entity\MemberWalletEntity;
use App\Infrastructure\Model\Member\MemberWallet;

/**
 * 会员钱包映射器.
 */
class MemberWalletMapper
{
    /**
     * 从 Model 转换为 Entity.
     */
    public static function fromModel(MemberWallet $model): MemberWalletEntity
    {
        $entity = new MemberWalletEntity();

        $entity->setId($model->id);
        $entity->setMemberId($model->member_id);
        $entity->setType($model->type);
        $entity->setBalance($model->balance);
        $entity->setFrozenBalance($model->frozen_balance);
        $entity->setTotalRecharge($model->total_recharge);
        $entity->setTotalConsume($model->total_consume);
        $entity->setStatus($model->status);

        return $entity;
    }

    /**
     * 获取新实体.
     */
    public static function getNewEntity(): MemberWalletEntity
    {
        return new MemberWalletEntity();
    }
}
