<?php

declare(strict_types=1);

// Deliberately do not load .env, the application container or any service pools.
// Unit tests must never connect to the developer's business database or Redis.
date_default_timezone_set('Asia/Shanghai');
defined('BASE_PATH') || define('BASE_PATH', dirname(__DIR__));
defined('SWOOLE_HOOK_FLAGS') || define('SWOOLE_HOOK_FLAGS', 0);
defined('START_TIME') || define('START_TIME', time());
defined('HF_VERSION') || define('HF_VERSION', '3.1');

$loader = require BASE_PATH . '/vendor/autoload.php';
$loader->addPsr4('HyperfTests\\', __DIR__ . '/');

// Loading a plugin's classes does not install, enable or boot that plugin.
// Tests can exercise both its implementation and its disabled fallback.
foreach (glob(BASE_PATH . '/plugins/*/plugin.json') ?: [] as $manifest) {
    $definition = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
    if (! empty($definition['namespace'])) {
        $loader->addPsr4(rtrim($definition['namespace'], '\\') . '\\', dirname($manifest) . '/src/');
    }
}

if (class_exists(\DG\BypassFinals::class)) {
    \DG\BypassFinals::enable();
}
