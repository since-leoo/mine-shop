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

namespace Plugin\SupplyChain;

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use Hyperf\AsyncQueue\Driver\RedisDriver;
use Plugin\SupplyChain\Factory\CredentialCipherFactory;
use Plugin\SupplyChain\Factory\SupplyChainFactory;
use Plugin\SupplyChain\Infrastructure\Listener\SupplyTradeListener;
use Plugin\SupplyChain\Infrastructure\Persistence\DatabaseApiLogRepository;
use Plugin\SupplyChain\Infrastructure\Persistence\DatabaseConnectionRepository;
use Plugin\SupplyChain\Infrastructure\Queue\AfterSaleConsumerProcess;
use Plugin\SupplyChain\Infrastructure\Queue\CatalogConsumerProcess;
use Plugin\SupplyChain\Infrastructure\Queue\MessageConsumerProcess;
use Plugin\SupplyChain\Infrastructure\Queue\OrderConsumerProcess;
use Plugin\SupplyChain\Infrastructure\Sdk\RedisRequestLimiter;
use Plugin\SupplyChain\Infrastructure\Sdk\RedisTokenStore;
use Plugin\SupplyChain\Infrastructure\Security\CredentialCipher;
use Plugin\SupplyChain\Interface\Adapter\SupplyFulfillmentAdapter;
use Plugin\SupplyChain\Interfaces\ApiLogRepositoryInterface;
use Plugin\SupplyChain\Interfaces\ConnectionRepositoryInterface;
use Plugin\SupplyChain\Interfaces\RequestLimiterInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;
use Plugin\SupplyChain\Interfaces\TokenStoreInterface;

use function Hyperf\Support\env;

final class ConfigProvider
{
    public function __invoke(): array
    {
        $queues = [];
        foreach (['supply_order' => 4, 'supply_catalog' => 2, 'supply_after_sale' => 2, 'supply_message' => 2] as $name => $limit) {
            $queues[$name] = [
                'driver' => RedisDriver::class,
                'redis' => ['pool' => 'default'],
                'channel' => '{' . $name . '-queue}',
                'timeout' => 2,
                'retry_seconds' => 30,
                'handle_timeout' => 180,
                'processes' => 1,
                'concurrent' => ['limit' => $limit],
                'max_messages' => 0,
            ];
        }
        return [
            'annotations' => ['scan' => ['paths' => [__DIR__]]],
            'dependencies' => [
                SupplyChainManagerInterface::class => SupplyChainFactory::class,
                ConnectionRepositoryInterface::class => DatabaseConnectionRepository::class,
                ApiLogRepositoryInterface::class => DatabaseApiLogRepository::class,
                TokenStoreInterface::class => RedisTokenStore::class,
                RequestLimiterInterface::class => RedisRequestLimiter::class,
                CredentialCipher::class => CredentialCipherFactory::class,
            ],
            'plugin_capabilities' => [ExternalFulfillmentInterface::class => SupplyFulfillmentAdapter::class],
            'listeners' => [SupplyTradeListener::class],
            'async_queue' => $queues,
            'processes' => [CatalogConsumerProcess::class, OrderConsumerProcess::class, AfterSaleConsumerProcess::class, MessageConsumerProcess::class],
            'supply_chain' => ['credential_keys' => ['primary' => (string) env('SUPPLY_CHAIN_ENCRYPTION_KEY', '')], 'active_key_id' => 'primary'],
        ];
    }
}
