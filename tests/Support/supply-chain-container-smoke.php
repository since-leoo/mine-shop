<?php

declare(strict_types=1);

// Fresh process and fresh generated proxies; never reads the application's .env or install locks.
$source = dirname(__DIR__, 2);
$enabled = ($argv[1] ?? '') === 'enabled';
$base = '/tmp/mine-shop-tests.supply-container-' . bin2hex(random_bytes(6));
mkdir($base, 0700);
define('BASE_PATH', $base); define('SWOOLE_HOOK_FLAGS', 0); define('START_TIME', time()); define('HF_VERSION', '3.1');
date_default_timezone_set('Asia/Shanghai'); putenv('JWT_SECRET=' . base64_encode(str_repeat('fixture-only-', 8)));
putenv('SUPPLY_CHAIN_ENCRYPTION_KEY=');
foreach (['vendor', 'composer.json', 'composer.lock', 'app'] as $item) { symlink($source . '/' . $item, $base . '/' . $item); }
mkdir($base . '/config'); mkdir($base . '/config/autoload'); mkdir($base . '/plugins');
foreach (glob($source . '/config/*.php') as $file) { copy($file, $base . '/config/' . basename($file)); }
foreach (glob($source . '/config/autoload/*.php') as $file) { copy($file, $base . '/config/autoload/' . basename($file)); }
foreach (glob($source . '/plugins/*/plugin.json') as $file) {
    $name = basename(dirname($file)); $target = $base . '/plugins/' . $name; mkdir($target);
    $manifest = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if ($name === 'supply-chain') { $manifest['enabled'] = $enabled; }
    file_put_contents($target . '/plugin.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    foreach (glob(dirname($file) . '/*') as $item) {
        if (! in_array(basename($item), ['plugin.json', 'install.lock'], true)) { symlink($item, $target . '/' . basename($item)); }
    }
    file_put_contents($target . '/install.lock', json_encode(['fixture' => 'temporary-only', 'installed_at' => date('c')]));
}
file_put_contents($base . '/config/autoload/databases.php', '<?php return ["default" => ["driver"=>"mysql", "unix_socket"=>"/tmp/mine-shop-tests.invalid/mysql.sock", "database"=>"nonexistent_smoke", "username"=>"root", "password"=>"", "prefix"=>""]];');
file_put_contents($base . '/config/autoload/redis.php', '<?php return ["default" => ["host"=>"/tmp/mine-shop-tests.invalid/redis.sock", "port"=>0, "db"=>0]];');
require $base . '/vendor/autoload.php';
try {
    SinceLeoo\Plugin\PluginBootstrap::init(); Hyperf\Di\ClassLoader::init();
    $container = require $base . '/config/container.php';
    $network = new class($container) extends Hyperf\Guzzle\ClientFactory {
        public int $requests = 0;
        public function create(array $options = []): GuzzleHttp\Client
        {
            return new GuzzleHttp\Client(['handler' => function (): never { ++$this->requests; throw new RuntimeException('Network forbidden in container smoke test'); }]);
        }
    };
    $container->set(Hyperf\Guzzle\ClientFactory::class, $network);
    $targets = [
        App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface::class,
        App\Domain\Infrastructure\Plugin\Contract\CommerceProductInterface::class,
        App\Domain\Infrastructure\Plugin\Contract\CommerceInventoryInterface::class,
        App\Domain\Infrastructure\Plugin\Contract\CommerceOrderInterface::class,
        App\Domain\Infrastructure\Plugin\Contract\CommerceAfterSaleInterface::class,
        App\Domain\Infrastructure\Plugin\Contract\PluginPermissionRegistrarInterface::class,
        App\Domain\Trade\Payment\DomainPayService::class,
        App\Application\Api\Order\AppTradeRecoveryService::class,
    ];
    if ($enabled) {
        array_push($targets,
            Plugin\SupplyChain\Interface\Admin\SupplyChainController::class,
            Plugin\SupplyChain\Application\Commad\AppSupplyTaskRunner::class,
            Plugin\SupplyChain\Application\Commad\AppSupplyScheduler::class,
            Plugin\SupplyChain\Application\Commad\AppSupplyWorkflow::class,
            Plugin\SupplyChain\Infrastructure\Crontab\SupplySchedulerCrontab::class,
            Plugin\SupplyChain\Infrastructure\Listener\SupplyTradeListener::class,
            Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface::class,
            Plugin\SupplyChain\Interfaces\ConnectionRepositoryInterface::class,
            Plugin\SupplyChain\Interfaces\ApiLogRepositoryInterface::class,
            Plugin\SupplyChain\Interfaces\TokenStoreInterface::class,
            Plugin\SupplyChain\Interfaces\RequestLimiterInterface::class,
        );
    }
    $result = ['resolved' => []];
    foreach ($targets as $target) { $result['resolved'][$target] = $container->get($target)::class; }
    $config = $container->get(Hyperf\Contract\ConfigInterface::class);
    $result['queues'] = array_values(array_filter(array_keys($config->get('async_queue', [])), static fn ($key) => str_starts_with($key, 'supply_')));
    $result['processes'] = array_values(array_filter($config->get('processes', []), static fn ($class) => str_starts_with($class, 'Plugin\\SupplyChain\\')));
    $result['plugin_autoloadable'] = class_exists('Plugin\\SupplyChain\\ConfigProvider');
    $provider = $container->get(Psr\EventDispatcher\ListenerProviderInterface::class);
    $result['events'] = [];
    foreach ([App\Domain\Trade\Order\Event\OrderPaidIntegrationEvent::class, App\Domain\Trade\Order\Event\OrderCancelledIntegrationEvent::class, App\Domain\Trade\Order\Event\OrderCompletedEvent::class, App\Domain\Trade\AfterSale\Event\AfterSaleChangedIntegrationEvent::class, App\Domain\Trade\AfterSale\Event\AfterSaleRefundSucceeded::class] as $event) {
        $result['events'][$event] = 0;
        foreach ($provider->getListenersForEvent((new ReflectionClass($event))->newInstanceWithoutConstructor()) as $listener) {
            if (is_array($listener) && is_object($listener[0]) && $listener[0]::class === 'Plugin\\SupplyChain\\Infrastructure\\Listener\\SupplyTradeListener') { ++$result['events'][$event]; }
        }
    }
    $capability = $container->get(App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface::class);
    $payment = $container->get(App\Domain\Trade\Payment\DomainPayService::class);
    $result['payment_capability_matches'] = (new ReflectionProperty($payment, 'fulfillment'))->getValue($payment) === $capability;
    if (! $enabled) {
        $capability->assertAvailable('merchant');
        try { $capability->assertAvailable('supply_chain', ['connection_id' => 1]); $result['external_rejected'] = false; }
        catch (DomainException) { $result['external_rejected'] = true; }
    }
    $result['network_requests'] = $network->requests;
    $result['temporary_lock'] = is_file($base . '/plugins/supply-chain/install.lock') && ! is_link($base . '/plugins/supply-chain/install.lock');
    echo json_encode($result, JSON_THROW_ON_ERROR) . PHP_EOL;
} finally {
    $remove = static function (string $path) use (&$remove): void {
        if (is_dir($path) && ! is_link($path)) { foreach (scandir($path) as $item) { if ($item !== '.' && $item !== '..') { $remove($path . '/' . $item); } } rmdir($path); }
        else { unlink($path); }
    };
    $remove($base);
}
