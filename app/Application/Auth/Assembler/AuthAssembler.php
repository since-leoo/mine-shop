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

namespace App\Application\Auth\Assembler;

use App\Domain\Auth\Entity\LoginEntity;
use App\Domain\Auth\Enum\Type;
use App\Domain\Auth\ValueObject\ClientInfo;

/**
 * Auth 模块组装器.
 */
final class AuthAssembler
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function toLoginEntity(array $payload): LoginEntity
    {
        $client = (new ClientInfo())
            ->setIp((string) ($payload['ip'] ?? '0.0.0.0'))
            ->setOs((string) ($payload['os'] ?? 'unknown'))
            ->setBrowser((string) ($payload['browser'] ?? 'unknown'));

        $type = $payload['user_type'] ?? Type::SYSTEM;
        $type = $type instanceof Type ? $type : (Type::tryFrom((int) $type) ?? Type::SYSTEM);

        return (new LoginEntity())
            ->setUsername((string) ($payload['username'] ?? ''))
            ->setPassword((string) ($payload['password'] ?? ''))
            ->setUserType($type)
            ->setClient($client);
    }
}
