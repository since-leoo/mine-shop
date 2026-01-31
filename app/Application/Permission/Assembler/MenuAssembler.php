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
use App\Domain\Permission\Entity\MenuEntity;

final class MenuAssembler
{
    public static function toCreateEntity(array $payload): MenuEntity
    {
        return self::map($payload, new MenuEntity());
    }

    public static function toUpdateEntity(int $id, array $payload): MenuEntity
    {
        $entity = (new MenuEntity())->setId($id);
        return self::map($payload, $entity);
    }

    private static function map(array $payload, MenuEntity $entity): MenuEntity
    {
        \array_key_exists('parent_id', $payload) && $entity->setParentId((int) $payload['parent_id']);
        \array_key_exists('name', $payload) && $entity->setName((string) $payload['name']);
        \array_key_exists('path', $payload) && $entity->setPath(self::nullableString($payload['path']));
        \array_key_exists('component', $payload) && $entity->setComponent(self::nullableString($payload['component']));
        \array_key_exists('redirect', $payload) && $entity->setRedirect(self::nullableString($payload['redirect']));
        \array_key_exists('status', $payload) && $entity->setStatus(self::toStatus($payload['status']));
        \array_key_exists('sort', $payload) && $entity->setSort((int) $payload['sort']);
        \array_key_exists('remark', $payload) && $entity->setRemark(self::nullableString($payload['remark']));
        \array_key_exists('meta', $payload) && $entity->setMeta((array) $payload['meta']);
        \array_key_exists('created_by', $payload) && $entity->setCreatedBy((int) $payload['created_by']);
        \array_key_exists('updated_by', $payload) && $entity->setUpdatedBy((int) $payload['updated_by']);
        \array_key_exists('btnPermission', $payload) && $entity->setButtonPermissions((array) $payload['btnPermission']);

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
