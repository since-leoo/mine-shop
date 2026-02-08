#!/usr/bin/env php
<?php

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');
date_default_timezone_set('Asia/Shanghai');

error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);
! defined('START_TIME') && define('START_TIME', time());    // 启动时间
! defined('HF_VERSION') && define('HF_VERSION', '3.1');     // 定义hyperf版本号

require BASE_PATH . '/vendor/autoload.php';

// 在 ClassLoader::init() 之前注册插件的 PSR-4 自动加载
// 这样 Hyperf 的注解扫描才能发现插件的 ConfigProvider 和依赖绑定
(function () {
    $pluginsPath = BASE_PATH . '/plugins';
    if (! is_dir($pluginsPath)) {
        return;
    }
    $loader = require BASE_PATH . '/vendor/autoload.php';
    foreach (scandir($pluginsPath) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $pluginPath = $pluginsPath . '/' . $item;
        $jsonFile = $pluginPath . '/plugin.json';
        if (! is_file($jsonFile)) {
            continue;
        }
        $config = json_decode(file_get_contents($jsonFile), true);
        if (empty($config['namespace']) || empty($config['enabled']) || ! file_exists($pluginPath . '/install.lock')) {
            continue;
        }
        $namespace = rtrim($config['namespace'], '\\') . '\\';
        $srcPath = $pluginPath . '/src/';
        if (is_dir($srcPath)) {
            $loader->addPsr4($namespace, $srcPath);
        }
        // 加载插件的 helper 文件
        $helperFile = $pluginPath . '/src/Helper/helper.php';
        if (is_file($helperFile)) {
            require_once $helperFile;
        }
    }
})();

// Self-called anonymous function that creates its own scope and keep the global namespace clean.
(function () {
    Hyperf\Di\ClassLoader::init();
    /** @var Psr\Container\ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';

    $application = $container->get(Hyperf\Contract\ApplicationInterface::class);
    $application->run();
})();
