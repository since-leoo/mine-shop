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

namespace App\Application\Product\Assembler;

use App\Domain\Product\Entity\CategoryEntity;
use App\Domain\Product\Enum\CategoryStatus;

/**
 * 分类组装器：负责将请求数据转换为领域实体.
 */
final class CategoryAssembler
{
    /**
     * 从请求数据创建分类实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): CategoryEntity
    {
        return (new CategoryEntity())
            ->setParentId((int) ($payload['parent_id'] ?? 0))
            ->setName($payload['name'] ?? '')
            ->setIcon($payload['icon'] ?? null)
            ->setThumbnail($payload['thumbnail'] ?? ($payload['icon'] ?? null))
            ->setDescription($payload['description'] ?? null)
            ->setSort((int) ($payload['sort'] ?? 0))
            ->setStatus($payload['status'] ?? CategoryStatus::ACTIVE->value);
    }

    /**
     * 从请求数据创建更新实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): CategoryEntity
    {
        $entity = (new CategoryEntity())->setId($id);

        isset($payload['parent_id']) && $entity->setParentId((int) $payload['parent_id']);
        isset($payload['name']) && $entity->setName($payload['name']);
        \array_key_exists('icon', $payload) && $entity->setIcon($payload['icon']);
        \array_key_exists('thumbnail', $payload) && $entity->setThumbnail($payload['thumbnail']);
        \array_key_exists('description', $payload) && $entity->setDescription($payload['description']);
        isset($payload['sort']) && $entity->setSort((int) $payload['sort']);
        isset($payload['status']) && $entity->setStatus($payload['status']);

        return $entity;
    }
}
