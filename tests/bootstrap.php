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
use DG\BypassFinals;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Di\ClassLoader;
/*
 * This file is part of MineAdmin.
 *
 * @see     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(\E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', \SWOOLE_HOOK_ALL);
! defined('START_TIME') && define('START_TIME', time());    // 启动时间
! defined('HF_VERSION') && define('HF_VERSION', '3.1');     // 定义hyperf版本号

require BASE_PATH . '/vendor/autoload.php';

// Enable BypassFinals before any class loading so final classes can be mocked in tests
if (class_exists(BypassFinals::class)) {
    BypassFinals::enable();
}

// 在 ClassLoader::init() 之前注册插件的 PSR-4 自动加载
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

ClassLoader::init();

$container = require BASE_PATH . '/config/container.php';

$container->get(ApplicationInterface::class);
