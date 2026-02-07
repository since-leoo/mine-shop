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

namespace App\Domain\Marketing\Seckill\Trait;

use App\Domain\Marketing\Seckill\Entity\SeckillActivityEntity;
use App\Domain\Marketing\Seckill\ValueObject\ActivityPeriod;
use App\Domain\Marketing\Seckill\ValueObject\ActivityRules;

/**
 * 秒杀活动实体转换Trait.
 */
trait SeckillActivityEntityTrait
{
    /**
     * 从数组创建实体.
     */
    public function createEntityFromArray(array $data): SeckillActivityEntity
    {
        $period = null;
        if (isset($data['start_time'], $data['end_time'])) {
            $period = new ActivityPeriod($data['start_time'], $data['end_time']);
        }

        $rules = isset($data['rules']) && \is_array($data['rules'])
            ? new ActivityRules($data['rules'])
            : ActivityRules::default();

        return SeckillActivityEntity::create(
            title: $data['title'],
            description: $data['description'] ?? null,
            period: $period,
            rules: $rules,
            remark: $data['remark'] ?? null
        );
    }

    /**
     * 从实体转换为数组.
     */
    public function entityToArray(SeckillActivityEntity $entity): array
    {
        return $entity->toArray();
    }

    /**
     * 验证活动规则数据.
     */
    public function validateRulesData(array $rules): bool
    {
        try {
            new ActivityRules($rules);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * 验证活动时间段.
     */
    public function validatePeriod(string $startTime, string $endTime): bool
    {
        try {
            new ActivityPeriod($startTime, $endTime);
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
}
