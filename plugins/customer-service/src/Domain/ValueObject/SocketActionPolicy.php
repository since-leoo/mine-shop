<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\ValueObject;

final class SocketActionPolicy
{
    public static function permission(string $action): string
    {
        return match ($action) {
            'read', 'ping', 'auth' => 'customer-service:conversation:read',
            'agent:accept' => 'customer-service:conversation:accept',
            'message:send' => 'customer-service:conversation:reply',
            'conversation:transfer' => 'customer-service:conversation:transfer',
            'conversation:close' => 'customer-service:conversation:close',
            default => throw new \DomainException('不支持的客服操作'),
        };
    }
}
