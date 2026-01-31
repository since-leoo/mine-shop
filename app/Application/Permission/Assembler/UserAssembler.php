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
use App\Domain\Auth\Enum\Type;
use App\Domain\Permission\Entity\UserEntity;
use App\Domain\Permission\Enum\DataPermission\PolicyType;
use App\Domain\Permission\ValueObject\DataPolicy;

final class UserAssembler
{
    public static function toCreateEntity(array $payload): UserEntity
    {
        return self::map($payload, new UserEntity());
    }

    public static function toUpdateEntity(int $id, array $payload): UserEntity
    {
        $entity = (new UserEntity())->setId($id);
        return self::map($payload, $entity);
    }

    private static function map(array $payload, UserEntity $entity): UserEntity
    {
        self::fillBasicFields($payload, $entity);

        \array_key_exists('department', $payload) && $entity->setDepartmentIds(self::intArray($payload['department']));
        \array_key_exists('position', $payload) && $entity->setPositionIds(self::intArray($payload['position']));

        if (\array_key_exists('policy', $payload) && \is_array($payload['policy'])) {
            $entity->setPolicy(self::buildPolicy($payload['policy']));
        }

        return $entity;
    }

    private static function fillBasicFields(array $payload, UserEntity $entity): void
    {
        \array_key_exists('username', $payload) && $entity->setUsername((string) $payload['username']);
        \array_key_exists('password', $payload) && $entity->setPassword($payload['password'] === null ? null : (string) $payload['password']);
        \array_key_exists('user_type', $payload) && $entity->setUserType(self::toUserType($payload['user_type']));
        \array_key_exists('nickname', $payload) && $entity->setNickname((string) $payload['nickname']);
        \array_key_exists('phone', $payload) && $entity->setPhone(self::nullableString($payload['phone']));
        \array_key_exists('email', $payload) && $entity->setEmail(self::nullableString($payload['email']));
        \array_key_exists('avatar', $payload) && $entity->setAvatar(self::nullableString($payload['avatar']));
        \array_key_exists('signed', $payload) && $entity->setSigned(self::nullableString($payload['signed']));
        \array_key_exists('status', $payload) && $entity->setStatus(self::toStatus($payload['status']));
        \array_key_exists('backend_setting', $payload) && $entity->setBackendSetting((array) $payload['backend_setting']);
        \array_key_exists('remark', $payload) && $entity->setRemark(self::nullableString($payload['remark']));
        \array_key_exists('created_by', $payload) && $entity->setCreatedBy((int) $payload['created_by']);
        \array_key_exists('updated_by', $payload) && $entity->setUpdatedBy((int) $payload['updated_by']);
    }

    private static function toUserType(mixed $userType): Type
    {
        if ($userType instanceof Type) {
            return $userType;
        }

        return Type::tryFrom((int) $userType) ?? Type::SYSTEM;
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

    /**
     * @param array<int|string, mixed> $policy
     */
    private static function buildPolicy(array $policy): ?DataPolicy
    {
        if ($policy === []) {
            return null;
        }
        $policyVo = new DataPolicy();
        isset($policy['id']) && $policyVo->setId((int) $policy['id']);
        isset($policy['policy_type']) && $policyVo->setType(self::toPolicyType($policy['policy_type']));
        isset($policy['value']) && $policyVo->setValue((array) $policy['value']);
        return $policyVo;
    }

    private static function toPolicyType(mixed $policyType): PolicyType
    {
        if ($policyType instanceof PolicyType) {
            return $policyType;
        }

        return PolicyType::tryFrom((string) $policyType) ?? PolicyType::Self;
    }

    /**
     * @param null|array<int|string, mixed> $values
     * @return int[]
     */
    private static function intArray(mixed $values): array
    {
        if (! \is_array($values)) {
            return [];
        }

        return array_values(array_map('intval', $values));
    }
}
