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

namespace App\Application\Member\Assembler;

use App\Domain\Member\Entity\MemberLevelEntity;

/**
 * 会员等级装配器.
 */
final class MemberLevelAssembler
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): MemberLevelEntity
    {
        $entity = new MemberLevelEntity();
        self::fill($entity, $payload);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): MemberLevelEntity
    {
        $entity = new MemberLevelEntity();
        $entity->setId($id);
        self::fill($entity, $payload);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function fill(MemberLevelEntity $entity, array $payload): void
    {
        $entity->setName($payload['name'] ?? null);
        $entity->setLevel(isset($payload['level']) ? (int) $payload['level'] : null);
        $entity->setGrowthMin(isset($payload['growth_value_min']) ? (int) $payload['growth_value_min'] : null);
        $entity->setGrowthMax(isset($payload['growth_value_max']) ? (int) $payload['growth_value_max'] : null);
        $entity->setDiscountRate(isset($payload['discount_rate']) ? (float) $payload['discount_rate'] : null);
        $entity->setPointRate(isset($payload['point_rate']) ? (float) $payload['point_rate'] : null);
        $entity->setPrivileges($payload['privileges'] ?? null);
        $entity->setIcon($payload['icon'] ?? null);
        $entity->setColor($payload['color'] ?? null);
        $entity->setStatus($payload['status'] ?? null);
        $entity->setSortOrder(isset($payload['sort_order']) ? (int) $payload['sort_order'] : null);
        $entity->setDescription($payload['description'] ?? null);
    }
}
