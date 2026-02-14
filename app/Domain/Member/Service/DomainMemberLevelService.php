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
use App\Domain\Member\Contract\MemberLevelInput;
use App\Domain\Member\Repository\MemberLevelRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Member\MemberLevel;
use App\Interface\Common\ResultCode;

final class DomainMemberLevelService extends IService
{
    public function __construct(
        public readonly MemberLevelRepository $repository,
        private readonly DomainMallSettingService $mallSettingService,
    ) {}

    /**
     * 创建会员等级.
     */
    public function create(MemberLevelInput $dto): MemberLevel
    {
        // 使用 DTO 的 toArray() 方法获取数据
        return $this->repository->create($dto->toArray());
    }

    /**
     * 更新会员等级.
     */
    public function update(MemberLevelInput $dto): ?MemberLevel
    {
        $level = $this->repository->findById($dto->getId());
        if (! $level) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');
        }

        // 使用 DTO 的 toArray() 方法获取数据
        $this->repository->updateById($dto->getId(), $dto->toArray());

        return $level->refresh();
    }

    /**
     * 删除会员等级.
     */
    public function delete(int $id): bool
    {
        if (! $this->repository->existsById($id)) {
            throw new BusinessException(ResultCode::NOT_FOUND, '会员等级不存在');
        }

        return $this->repository->deleteById($id) > 0;
    }

    /**
     * 校验等级配置列表的合法性（序号唯一、成长值门槛严格递增）.
     *
     * @param array $levels 等级配置数组，每项需包含 level 和 growth_value_min 字段
     * @throws BusinessException 校验失败时抛出
     */
    public function validateLevelConfigs(array $levels): void
    {
        if (empty($levels)) {
            return;
        }

        // 校验序号唯一性
        $levelNumbers = array_column($levels, 'level');
        if (count($levelNumbers) !== count(array_unique($levelNumbers))) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '等级序号不能重复');
        }

        // 按序号升序排序后校验成长值门槛严格递增
        usort($levels, static fn (array $a, array $b) => $a['level'] <=> $b['level']);

        for ($i = 1, $count = count($levels); $i < $count; ++$i) {
            if ($levels[$i]['growth_value_min'] <= $levels[$i - 1]['growth_value_min']) {
                throw new BusinessException(
                    ResultCode::UNPROCESSABLE_ENTITY,
                    sprintf(
                        '等级 %d 的成长值门槛必须大于等级 %d 的成长值门槛',
                        $levels[$i]['level'],
                        $levels[$i - 1]['level'],
                    ),
                );
            }
        }
    }

    /**
     * 根据成长值匹配对应等级（返回所有启用等级中 growth_value_min <= growthValue 的最高序号等级）.
     *
     * @param int $growthValue 当前成长值
     * @return MemberLevel 匹配的等级
     */
    public function matchLevelByGrowthValue(int $growthValue): MemberLevel
    {
        $matched = $this->repository->getModel()->newQuery()
            ->where('status', 'active')
            ->where('growth_value_min', '<=', $growthValue)
            ->orderByDesc('level')
            ->first();

        if ($matched) {
            return $matched;
        }

        // 回退到系统默认等级
        $defaultLevelId = $this->mallSettingService->member()->defaultLevel();
        $defaultLevel = $this->repository->findById($defaultLevelId);

        if ($defaultLevel) {
            return $defaultLevel;
        }

        // 如果默认等级也不存在，返回序号最小的启用等级
        return $this->repository->getModel()->newQuery()
            ->where('status', 'active')
            ->orderBy('level')
            ->firstOrFail();
    }

    /**
     * 获取所有启用的等级列表（按等级序号升序）.
     *
     * @return MemberLevel[]
     */
    public function getActiveLevels(): array
    {
        return $this->repository->getModel()->newQuery()
            ->where('status', 'active')
            ->orderBy('level')
            ->get()
            ->all();
    }
}
