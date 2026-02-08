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

namespace Plugin\Wechat\Factory;

use Hyperf\Contract\ConfigInterface;
use Plugin\Wechat\Handler\MiniAppAppHandler;
use Psr\Container\ContainerInterface;

class MiniAppFactory
{
    /**
     * 该函数用于将当前类实例作为一个可调用对象
     * 主要用于依赖注入容器，通过此函数可以创建并配置 MiniHandler 类的实例.
     *
     * @param ContainerInterface $container 依赖注入容器对象，用于获取配置信息
     * @return MiniAppAppHandler 返回一个 MiniHandler 实例，用于处理微信小程序相关的逻辑
     */
    public function __invoke(ContainerInterface $container)
    {
        // 从依赖注入容器中获取 ConfigInterface 实例，用于访问配置信息
        $config = $container->get(ConfigInterface::class);
        // 从配置中提取与微信相关的选项，并默认为空数组，确保即使没有配置也能正常初始化
        $option = $config->get('wechat', []);

        if (empty($option)) {
            throw new \Exception('wechat config is empty');
        }

        // 使用依赖注入的方式创建并返回一个 MiniHandler 实例，传入微信配置选项作为构造函数参数
        return \Hyperf\Support\make(MiniAppAppHandler::class, [$option]);
    }
}
