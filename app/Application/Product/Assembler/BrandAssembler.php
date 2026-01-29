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

use App\Domain\Product\Entity\BrandEntity;
use App\Domain\Product\Enum\BrandStatus;

/**
 * 品牌组装器：负责将请求数据转换为领域实体.
 */
final class BrandAssembler
{
    /**
     * 从请求数据创建品牌实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toCreateEntity(array $payload): BrandEntity
    {
        return (new BrandEntity())
            ->setName($payload['name'] ?? '')
            ->setLogo($payload['logo'] ?? null)
            ->setDescription($payload['description'] ?? null)
            ->setWebsite($payload['website'] ?? null)
            ->setSort((int) ($payload['sort'] ?? 0))
            ->setStatus($payload['status'] ?? BrandStatus::ACTIVE->value);
    }

    /**
     * 从请求数据创建更新实体.
     *
     * @param array<string, mixed> $payload
     */
    public static function toUpdateEntity(int $id, array $payload): BrandEntity
    {
        $entity = (new BrandEntity())->setId($id);

        isset($payload['name']) && $entity->setName($payload['name']);
        \array_key_exists('logo', $payload) && $entity->setLogo($payload['logo']);
        \array_key_exists('description', $payload) && $entity->setDescription($payload['description']);
        \array_key_exists('website', $payload) && $entity->setWebsite($payload['website']);
        isset($payload['sort']) && $entity->setSort((int) $payload['sort']);
        isset($payload['status']) && $entity->setStatus($payload['status']);

        return $entity;
    }
}
