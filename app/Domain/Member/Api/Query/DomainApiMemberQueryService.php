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

namespace App\Domain\Member\Api\Query;

use App\Domain\Member\Repository\MemberRepository;
use App\Domain\Member\Service\DomainMemberLevelService;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

/**
 * 面向 API 场景的会员查询领域服务.
 */

final class DomainApiMemberQueryService extends IService
{
    public function __construct(
        public readonly MemberRepository $repository,
        private readonly DomainMemberLevelService $levelService,
    ) {}

    /**
     * 会员详情.
     *
     * @return null|array<string, mixed>
     */
    public function detail(int $memberId): ?array
    {
        return $this->repository->detail($memberId);
    }

    /**
     * 查询会员VIP信息.
     *
     * 返回当前等级名称、图标、成长值、升级所需成长值、权益列表.
     *
     * @return array{level_name: string, level_icon: string|null, growth_value: int, next_level_gap: int, privileges: array}
     */
    public function getVipInfo(int $memberId): array
    {
        $member = $this->repository->findById($memberId);
        if (! $member) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员不存在');
        }

        $growthValue = (int) $member->growth_value;

        // 获取当前等级
        $currentLevel = $this->levelService->matchLevelByGrowthValue($growthValue);

        // 获取所有启用等级（按序号升序），计算下一等级差值
        $activeLevels = $this->levelService->getActiveLevels();
        $nextLevelGap = 0;

        foreach ($activeLevels as $level) {
            if ($level->level > $currentLevel->level) {
                $nextLevelGap = $level->growth_value_min - $growthValue;
                break;
            }
        }

        return [
            'level_name' => $currentLevel->name,
            'level_icon' => $currentLevel->icon,
            'growth_value' => $growthValue,
            'next_level_gap' => max($nextLevelGap, 0),
            'privileges' => $currentLevel->privileges ?? [],
        ];
    }
}

