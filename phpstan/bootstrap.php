<?php

declare(strict_types=1);

// 仅注册插件命名空间，不启动应用或读取业务环境。
$loader = require dirname(__DIR__) . '/vendor/autoload.php';
foreach (glob(dirname(__DIR__) . '/plugins/*/plugin.json') ?: [] as $manifest) {
    $definition = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
    if (! empty($definition['namespace'])) {
        $loader->addPsr4(rtrim($definition['namespace'], '\\') . '\\', dirname($manifest) . '/src/');
    }
}
