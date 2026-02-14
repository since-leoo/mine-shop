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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Member\Event\MemberBalanceAdjusted;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Repository\MemberWalletTransactionRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

/**
 * 积分领域服务：封装积分计算、发放、扣回逻辑.
 * 不派发事件，事件由应用层/Listener 编排。
 */
final class DomainMemberPointsService
{
    public function __construct(
        private readonly DomainMemberWalletService $walletService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly MemberRepository $memberRepository,
        private readonly MemberLevelRepository $levelRepository,
        private readonly MemberWalletTransactionRepository $transactionRepository,
    ) {}

    /**
     * 计算消费返积分数量（纯计算方法）.
     *
     * 公式：floor(payAmountCents / 100 * pointsRatio * pointRate)
     */
    public function calculatePurchasePoints(int $payAmountCents, float $pointRate): int
    {
        $pointsRatio = $this->mallSettingService->member()->pointsRatio();

        return (int) floor($payAmountCents / 100 * $pointsRatio * $pointRate);
    }

    /**
     * 注册赠送积分.
     *
     * @return ?MemberBalanceAdjusted 变动事件对象供调用方派发（无变动时返回 null）
     */
    public function grantRegisterPoints(int $memberId): ?MemberBalanceAdjusted
    {
        $registerPoints = $this->mallSettingService->member()->registerPoints();

        if ($registerPoints <= 0) {
            return null;
        }

        $member = $this->memberRepository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        // 幂等性检查：同一会员仅赠送一次注册积分
        if ($this->transactionRepository->existsByMemberAndSource($memberId, 'points', 'register')) {
            return null;
        }

        $walletEntity = $this->walletService->getEntity($memberId, 'points');
        $walletEntity->grant($registerPoints, 'register', '注册赠送积分');
        $this->walletService->saveEntity($walletEntity);

        return new MemberBalanceAdjusted(
            memberId: $memberId,
            walletId: $walletEntity->getId(),
            walletType: 'points',
            changeAmount: $registerPoints,
            beforeBalance: $walletEntity->getBeforeBalance(),
            afterBalance: $walletEntity->getAfterBalance(),
            source: 'register',
            remark: '注册赠送积分',
        );
    }

    /**
     * 消费返积分.
     *
     * @return ?MemberBalanceAdjusted 变动事件对象供调用方派发（无变动时返回 null）
     */
    public function grantPurchasePoints(int $memberId, int $payAmountCents, string $orderNo): ?MemberBalanceAdjusted
    {
        $member = $this->memberRepository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $pointRate = 1.0;
        if ($member->level_id) {
            $level = $this->levelRepository->findById($member->level_id);
            if ($level) {
                $pointRate = (float) $level->point_rate;
            }
        }

        $points = $this->calculatePurchasePoints($payAmountCents, $pointRate);

        if ($points <= 0) {
            return null;
        }

        $walletEntity = $this->walletService->getEntity($memberId, 'points');
        $walletEntity->grant($points, 'purchase_reward', '消费奖励:' . $orderNo);
        $this->walletService->saveEntity($walletEntity);

        return new MemberBalanceAdjusted(
            memberId: $memberId,
            walletId: $walletEntity->getId(),
            walletType: 'points',
            changeAmount: $points,
            beforeBalance: $walletEntity->getBeforeBalance(),
            afterBalance: $walletEntity->getAfterBalance(),
            source: 'purchase_reward',
            remark: '消费奖励:' . $orderNo,
        );
    }

    /**
     * 扣回消费返积分（退款场景，余额不足时扣至零）.
     *
     * @return ?MemberBalanceAdjusted 变动事件对象供调用方派发（无变动时返回 null）
     */
    public function deductPurchasePoints(int $memberId, int $pointsToDeduct, string $orderNo): ?MemberBalanceAdjusted
    {
        if ($pointsToDeduct <= 0) {
            return null;
        }

        $member = $this->memberRepository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $walletEntity = $this->walletService->getEntity($memberId, 'points');
        $actualDeduction = $walletEntity->deductSafe($pointsToDeduct, 'purchase_refund', '退款扣回积分:' . $orderNo);

        if ($actualDeduction <= 0) {
            return null;
        }

        $this->walletService->saveEntity($walletEntity);

        return new MemberBalanceAdjusted(
            memberId: $memberId,
            walletId: $walletEntity->getId(),
            walletType: 'points',
            changeAmount: -$actualDeduction,
            beforeBalance: $walletEntity->getBeforeBalance(),
            afterBalance: $walletEntity->getAfterBalance(),
            source: 'purchase_refund',
            remark: '退款扣回积分:' . $orderNo,
        );
    }
}
