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

namespace App\Domain\Member\Service;

use App\Domain\Member\Contract\MemberWalletInput;
use App\Domain\Member\Entity\MemberWalletEntity;
use App\Domain\Member\Mapper\MemberWalletMapper;
use App\Domain\Member\Repository\MemberWalletRepository;
use App\Domain\Member\ValueObject\BalanceChangeVo;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\MemberWallet;

/**
 * 会员账户领域服务：负责钱包调整规则及持久化协调.
 */
final class DomainMemberWalletService extends IService
{
    public function __construct(
        private readonly MemberWalletRepository $repository,
    ) {}

    /**
     * 调整会员账户.
     */
    public function adjustBalance(MemberWalletInput $dto): BalanceChangeVo
    {
        // 1. 获取钱包实体
        $entity = $this->getEntity($dto->getMemberId(), $dto->getType());

        // 2. 调用实体的 adjustBalance 行为方法
        $result = $entity->adjustBalance($dto);

        // 3. 持久化修改
        $this->repository->save($entity);

        return $result;
    }

    /**
     * 持久化钱包实体（已在外部完成余额变更逻辑后调用）.
     */
    public function saveEntity(MemberWalletEntity $entity): void
    {
        $this->repository->save($entity);
    }

    /**
     * 获取钱包实体.
     *
     * 通过会员ID和钱包类型获取 Model，然后通过 Mapper 转换为 Entity.
     *
     * @param int $memberId 会员ID
     * @param string $type 钱包类型
     * @return MemberWalletEntity 钱包实体对象
     * @throws BusinessException 当钱包不存在时
     */
    public function getEntity(int $memberId, string $type): MemberWalletEntity
    {
        /** @var null|MemberWallet $model */
        $model = $this->repository->findByMemberIdAndType($memberId, $type);

        if (! $model) {
            // 自动创建钱包
            $entity = MemberWalletMapper::getNewEntity();
            $entity->setMemberId($memberId);
            $entity->setType($type);
            $this->repository->create($entity);
            return $entity;
        }

        return MemberWalletMapper::fromModel($model);
    }
}
