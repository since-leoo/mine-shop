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

use App\Domain\Member\Event\MemberGrowthChanged;
use App\Domain\Member\Repository\MemberGrowthLogRepository;
use App\Domain\Member\Repository\MemberRepository;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

/**
 * 成长值领域服务：负责成长值的增减和等级重算.
 * 不派发事件，事件由应用层/Listener 编排。
 */
final class DomainMemberGrowthService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly MemberGrowthLogRepository $growthLogRepository,
        private readonly DomainMemberLevelService $levelService,
    ) {}

    /**
     * 增加成长值，返回变动事件对象供调用方派发.
     *
     * @return ?MemberGrowthChanged 变动事件（无变动时返回 null）
     */
    public function addGrowthValue(int $memberId, int $amount, string $source, string $remark = ''): ?MemberGrowthChanged
    {
        if ($amount <= 0) {
            return null;
        }

        $member = $this->memberRepository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $beforeValue = (int) $member->growth_value;
        $afterValue = $beforeValue + $amount;

        $this->memberRepository->updateById($memberId, ['growth_value' => $afterValue]);

        $this->growthLogRepository->create([
            'member_id' => $memberId,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'change_amount' => $amount,
            'source' => $source,
            'remark' => $remark,
        ]);

        return new MemberGrowthChanged(
            memberId: $memberId,
            beforeValue: $beforeValue,
            afterValue: $afterValue,
            changeAmount: $amount,
            source: $source,
            remark: $remark,
        );
    }

    /**
     * 减少成长值（最低为0），返回变动事件对象供调用方派发.
     *
     * @return ?MemberGrowthChanged 变动事件（无变动时返回 null）
     */
    public function deductGrowthValue(int $memberId, int $amount, string $source, string $remark = ''): ?MemberGrowthChanged
    {
        if ($amount <= 0) {
            return null;
        }

        $member = $this->memberRepository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $beforeValue = (int) $member->growth_value;
        $actualDeduction = min($amount, $beforeValue);
        $afterValue = $beforeValue - $actualDeduction;

        if ($actualDeduction === 0) {
            return null;
        }

        $this->memberRepository->updateById($memberId, ['growth_value' => $afterValue]);

        $this->growthLogRepository->create([
            'member_id' => $memberId,
            'before_value' => $beforeValue,
            'after_value' => $afterValue,
            'change_amount' => -$actualDeduction,
            'source' => $source,
            'remark' => $remark,
        ]);

        return new MemberGrowthChanged(
            memberId: $memberId,
            beforeValue: $beforeValue,
            afterValue: $afterValue,
            changeAmount: -$actualDeduction,
            source: $source,
            remark: $remark,
        );
    }

    /**
     * 根据当前成长值重新计算并更新会员等级.
     */
    public function recalculateLevel(int $memberId): void
    {
        $member = $this->memberRepository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $matchedLevel = $this->levelService->matchLevelByGrowthValue((int) $member->growth_value);

        if ((int) $member->level_id !== $matchedLevel->id) {
            $this->memberRepository->updateById($memberId, [
                'level' => $matchedLevel->name,
                'level_id' => $matchedLevel->id,
            ]);
        }
    }
}
