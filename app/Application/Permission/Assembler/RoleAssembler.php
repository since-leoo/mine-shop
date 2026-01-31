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

namespace App\Application\Permission\Assembler;

use App\Domain\Auth\Enum\Status;
use App\Domain\Permission\Entity\RoleEntity;

final class RoleAssembler
{
    public static function toCreateEntity(array $payload): RoleEntity
    {
        return self::map($payload, new RoleEntity());
    }

    public static function toUpdateEntity(int $id, array $payload): RoleEntity
    {
        $entity = (new RoleEntity())->setId($id);
        return self::map($payload, $entity);
    }

    private static function map(array $payload, RoleEntity $entity): RoleEntity
    {
        \array_key_exists('name', $payload) && $entity->setName((string) $payload['name']);
        \array_key_exists('code', $payload) && $entity->setCode((string) $payload['code']);
        \array_key_exists('status', $payload) && $entity->setStatus(self::toStatus($payload['status']));
        \array_key_exists('sort', $payload) && $entity->setSort((int) $payload['sort']);
        \array_key_exists('remark', $payload) && $entity->setRemark(self::nullableString($payload['remark']));
        \array_key_exists('created_by', $payload) && $entity->setCreatedBy((int) $payload['created_by']);
        \array_key_exists('updated_by', $payload) && $entity->setUpdatedBy((int) $payload['updated_by']);

        return $entity;
    }

    private static function toStatus(mixed $status): Status
    {
        if ($status instanceof Status) {
            return $status;
        }

        return Status::tryFrom((int) $status) ?? Status::Normal;
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }
}
