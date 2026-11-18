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

use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;
use Plugin\SupplyChain\Interfaces\ConnectionRepositoryInterface;

final class SupplyChainSettingsResolver
{
    public function __construct(private readonly ConnectionRepositoryInterface $connections, private readonly CredentialCipher $cipher) {}

    public function resolve(int $connectionId): ConnectionConfig
    {
        $row = $this->connections->get($connectionId);
        if ((int) ($row['id'] ?? 0) !== $connectionId || ! ($row['enabled'] ?? false)) {
            throw SupplyChainError::CONNECTION_UNAVAILABLE->exception();
        }
        if (($row['driver'] ?? 'yunzhong') !== 'yunzhong') {
            throw SupplyChainError::DRIVER_UNSUPPORTED->exception();
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
