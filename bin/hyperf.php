#!/usr/bin/env php
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
use App\Infrastructure\Plugin\PluginScanCache;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Di\ClassLoader;
use Psr\Container\ContainerInterface;
use SinceLeoo\Plugin\PluginBootstrap;

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');
date_default_timezone_set('Asia/Shanghai');

error_reporting(\E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', defined('SWOOLE_HOOK_ALL') ? \SWOOLE_HOOK_ALL : 0);
! defined('START_TIME') && define('START_TIME', time());    // 启动时间
! defined('HF_VERSION') && define('HF_VERSION', '3.1');     // 定义hyperf版本号

require BASE_PATH . '/vendor/autoload.php';

// Self-called anonymous function that creates its own scope and keep the global namespace clean.
(function () {
    // 插件安装和启停后自动刷新生产扫描缓存；成功扫描后才记录当前插件指纹。
    PluginScanCache::initialize(BASE_PATH, static function (): void {
        // 自动加载和扫描共用启动锁，防止等待扫描期间使用旧插件清单。
        PluginBootstrap::init();
        ClassLoader::init();
    });
    /** @var ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';

    $application = $container->get(ApplicationInterface::class);
    $application->run();
})();
