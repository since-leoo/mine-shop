<?php

declare(strict_types=1);

// Each invocation has fresh Hyperf static collectors but reuses one private fixture cache.
$base = $argv[1] ?? '';
if (! str_starts_with($base, sys_get_temp_dir() . '/mine-plugin-scan-') || ! is_file($base . '/fixture.marker')) {
    throw new RuntimeException('A private plugin-scan fixture is required');
}
define('BASE_PATH', $base); define('SWOOLE_HOOK_FLAGS', 0); define('START_TIME', time()); define('HF_VERSION', '3.1');
putenv('APP_DEBUG=false');
$loader = require BASE_PATH . '/vendor/autoload.php';
$loader->addPsr4('ScanFixture\\', BASE_PATH . '/fixture/');
App\Infrastructure\Plugin\PluginScanCache::initialize(BASE_PATH, static function (): void {
    SinceLeoo\Plugin\PluginBootstrap::init();
    Hyperf\Di\ClassLoader::init();
});
$routes = (new Hyperf\HttpServer\Router\DispatcherFactory())->getDispatcher('http');
$route = $routes->dispatch('GET', '/admin/supply-chain/connections');
echo json_encode([
    'cacheable' => Hyperf\Di\Annotation\ScanConfig::instance(BASE_PATH . '/config')->isCacheable(),
    'host_route' => $routes->dispatch('GET', '/scan-fixture')[0],
    'route' => $route[0],
    'callback' => $route[1]->callback ?? null,
    'cache_modified' => filemtime(BASE_PATH . '/runtime/container/scan.cache'),
], JSON_THROW_ON_ERROR) . PHP_EOL;
