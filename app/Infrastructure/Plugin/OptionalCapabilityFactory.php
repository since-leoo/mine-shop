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

namespace App\Infrastructure\Plugin;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

/** 只解析已加载插件声明的能力；插件停用时使用核心的降级实现，避免反向依赖插件类。 */
final class OptionalCapabilityFactory
{
    public static function resolve(ContainerInterface $container, string $contract, string $fallback): object
    {
        $bindings = $container->get(ConfigInterface::class)->get('plugin_capabilities', []);
        $implementation = $bindings[$contract] ?? $fallback;
        if (! \is_string($implementation) || ! is_a($implementation, $contract, true)) {
            throw new \LogicException(\App\Infrastructure\Error\PluginError::CAPABILITY_BINDING_INVALID->message([$contract]), \App\Infrastructure\Error\PluginError::CAPABILITY_BINDING_INVALID->value);
        }
        $service = $container->get($implementation);
        if (! $service instanceof $contract) {
            throw new \LogicException(\App\Infrastructure\Error\PluginError::CAPABILITY_IMPLEMENTATION_INVALID->message([$contract]), \App\Infrastructure\Error\PluginError::CAPABILITY_IMPLEMENTATION_INVALID->value);
        }
        return $service;
    }
}
