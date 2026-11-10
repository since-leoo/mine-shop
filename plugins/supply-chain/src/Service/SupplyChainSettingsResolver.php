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

namespace Plugin\SupplyChain\Service;

use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;
use Plugin\SupplyChain\Interfaces\ConnectionRepositoryInterface;

final class SupplyChainSettingsResolver
{
    public function __construct(private readonly ConnectionRepositoryInterface $connections, private readonly CredentialCipher $cipher) {}

    public function resolve(int $connectionId): ConnectionConfig
    {
        $row = $this->connections->get($connectionId);
        if ((int) ($row['id'] ?? 0) !== $connectionId || ! ($row['enabled'] ?? false)) {
            throw new \DomainException('供应链连接不存在或已停用');
        }
        if (($row['driver'] ?? 'yunzhong') !== 'yunzhong') {
            throw new \DomainException('尚未支持此供应链驱动');
        }
        $secret = $this->cipher->decrypt((string) ($row['app_secret_ciphertext'] ?? ''), 'supply-connection:' . $connectionId);
        return new ConnectionConfig(
            $connectionId,
            rtrim((string) $row['api_domain'], '/') . '/' . trim((string) ($row['api_path'] ?? '/supplyapi'), '/'),
            (string) $row['app_key'],
            $secret,
            (int) ($row['credential_version'] ?? 1),
            (float) ($row['timeout_seconds'] ?? 15),
            (float) ($row['connect_timeout_seconds'] ?? 3)
        );
    }
}
