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

namespace App\Interface\Api\Support;

final class ApiSignatureConfig
{
    /**
     * @param null|array<string, mixed> $config
     */
    public function __construct(private readonly ?array $config = null) {}

    public function enabled(): bool
    {
        return (bool) $this->value('enabled', false);
    }

    public function ttl(): int
    {
        return (int) $this->value('ttl', 300);
    }

    public function clientSecret(string $clientId): ?string
    {
        $clients = $this->clients();
        $client = $clients[$clientId] ?? null;
        if (! \is_array($client)) {
            return null;
        }

        $secret = $client['secret'] ?? null;

        return \is_string($secret) && $secret !== '' ? $secret : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function clients(): array
    {
        $clients = $this->value('clients', []);

        return \is_array($clients) ? $clients : [];
    }

    private function value(string $key, mixed $default = null): mixed
    {
        if (\is_array($this->config)) {
            return $this->config[$key] ?? $default;
        }

        return config('api_signature.' . $key, $default);
    }
}
