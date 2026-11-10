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

use Hyperf\Guzzle\ClientFactory;
use Plugin\SupplyChain\Handler\YunzhongClientHandler;
use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Interfaces\ApiLogRepositoryInterface;
use Plugin\SupplyChain\Interfaces\RequestLimiterInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainClientInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;
use Plugin\SupplyChain\Interfaces\TokenStoreInterface;
use Plugin\SupplyChain\Service\ConnectionConfig;
use Plugin\SupplyChain\Service\SupplyChainManager;
use Plugin\SupplyChain\Service\SupplyChainSettingsResolver;
use Psr\Container\ContainerInterface;

final class SupplyChainFactory
{
    public function __invoke(ContainerInterface $container): SupplyChainManagerInterface
    {
        // Resolving the manager never reads a connection or decrypts credentials.
        return new SupplyChainManager(
            $container->get(SupplyChainSettingsResolver::class),
            static function (ConnectionConfig $config) use ($container): SupplyChainClientInterface {
                return new YunzhongClientHandler(new YunzhongClient(
                    $config,
                    $container->get(ClientFactory::class)->create(),
                    $container->get(TokenStoreInterface::class),
                    $container->get(ApiLogRepositoryInterface::class),
                    $container->get(RequestLimiterInterface::class)
                ));
            }
        );
    }
}
