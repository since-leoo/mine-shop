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

use App\Domain\Member\Entity\MemberWalletEntity;
use App\Domain\Member\Repository\MemberWalletRepository;

/**
 * 会员账户领域服务：负责钱包调整规则及持久化协调.
 */
final class MemberAccountService
{
    public function __construct(
        private readonly MemberWalletRepository $walletRepository,
    ) {}

    /**
     * 调整会员账户.
     */
    public function adjustBalance(MemberWalletEntity $wallet): MemberWalletEntity
    {
        $memberWalletEntity = $this->walletRepository->findEntity(
            $wallet->getMemberId(),
            $wallet->getType(),
        );

        $memberWalletEntity->setChangeBalance($wallet->getChangeBalance());
        $memberWalletEntity->setRemark($wallet->getRemark());
        $memberWalletEntity->setSource($wallet->getSource());
        $memberWalletEntity->changeBalance();

        $this->walletRepository->save($memberWalletEntity);

        return $memberWalletEntity;
    }
}
