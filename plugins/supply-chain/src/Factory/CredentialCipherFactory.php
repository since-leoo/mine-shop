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

namespace Plugin\SupplyChain\Factory;

use Hyperf\Contract\ConfigInterface;
use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;
use Psr\Container\ContainerInterface;

final class CredentialCipherFactory
{
    public function __invoke(ContainerInterface $container): CredentialCipher
    {
        $config = $container->get(ConfigInterface::class);
        return new CredentialCipher(
            (array) $config->get('supply_chain.credential_keys', []),
            (string) $config->get('supply_chain.active_key_id', 'primary')
        );
    }
}
