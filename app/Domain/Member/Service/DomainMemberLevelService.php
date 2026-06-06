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
     * Import mall.member.vip_levels into member_levels explicitly.
     *
     * The config is import input only. Runtime matching continues to read
     * member_levels, and unrelated table records are not deleted or changed.
     *
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importVipLevelsFromConfig(int $operatorId = 0): array
    {
        $levels = $this->normalizeVipLevelConfigs($this->mallSettingService->member()->vipLevels());
        $this->validateLevelConfigs($levels);
        $this->validateUniqueLevelNames($levels);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($levels as $level) {
            $existing = $this->repository->getModel()->newQuery()
                ->where('name', $level['name'])
                ->first();

            if ($existing instanceof MemberLevel) {
                $payload = $level;
                if ($operatorId > 0) {
                    $payload['updated_by'] = $operatorId;
                }
                $existing->update($payload);
                ++$result['updated'];
                continue;
            }

            $payload = $level;
            if ($operatorId > 0) {
                $payload['created_by'] = $operatorId;
            }
            $this->repository->create($payload);
            ++$result['created'];
        }

        return $result;
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
        if (\count($levelNumbers) !== \count(array_unique($levelNumbers))) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '等级序号不能重复');
        }

        // 按序号升序排序后校验成长值门槛严格递增
        usort($levels, static fn (array $a, array $b) => $a['level'] <=> $b['level']);

        for ($i = 1, $count = \count($levels); $i < $count; ++$i) {
            if ($levels[$i]['growth_value_min'] <= $levels[$i - 1]['growth_value_min']) {
                throw new BusinessException(
                    ResultCode::UNPROCESSABLE_ENTITY,
                    \sprintf(
                        '等级 %d 的成长值门槛必须大于等级 %d 的成长值门槛',
                        $levels[$i]['level'],
                        $levels[$i - 1]['level'],
                    ),
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $levels
     */
    private function validateUniqueLevelNames(array $levels): void
    {
        $names = array_map(static fn (array $level) => $level['name'], $levels);
        if (\count($names) !== \count(array_unique($names))) {
            throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '会员等级名称不能重复');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $levels
     * @return array<int, array<string, mixed>>
     */
    private function normalizeVipLevelConfigs(array $levels): array
    {
        $normalized = [];

        foreach ($levels as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '会员等级名称不能为空');
            }

            if (! isset($item['level']) || (! isset($item['growth']) && ! isset($item['growth_value_min']))) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '会员等级配置字段不完整');
            }

            $level = (int) $item['level'];
            $growthValueMin = (int) ($item['growth_value_min'] ?? $item['growth']);

            if ($level < 1 || $growthValueMin < 0) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, '会员等级配置字段不合法');
            }

            $payload = [
                'name' => $name,
                'level' => $level,
                'growth_value_min' => $growthValueMin,
                'status' => (string) ($item['status'] ?? 'active'),
                'sort_order' => (int) ($item['sort_order'] ?? $level),
            ];

            foreach (['growth_value_max', 'discount_rate', 'point_rate', 'privileges', 'icon', 'color', 'description'] as $field) {
                if (array_key_exists($field, $item) && $item[$field] !== null) {
                    $payload[$field] = $item[$field];
                }
            }

            $normalized[] = $payload;
        }

        return $normalized;
    }

    /**
     * Match the highest active table level whose threshold is not greater than the growth value.
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
