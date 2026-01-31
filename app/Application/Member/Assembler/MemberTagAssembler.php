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

use App\Domain\Member\Entity\MemberTagEntity;

/**
 * 会员标签装配器.
 */
final class MemberTagAssembler
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): MemberTagEntity
    {
        $entity = new MemberTagEntity();
        $entity->setName($payload['name'] ?? null);
        $entity->setColor($payload['color'] ?? null);
        $entity->setDescription($payload['description'] ?? null);
        $entity->setStatus($payload['status'] ?? null);
        $entity->setSortOrder(isset($payload['sort_order']) ? (int) $payload['sort_order'] : null);

        return $entity;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): MemberTagEntity
    {
        $entity = self::toCreateEntity($payload);
        $entity->setId($id);
        return $entity;
    }
}
