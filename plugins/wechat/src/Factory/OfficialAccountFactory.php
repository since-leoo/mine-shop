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

use Plugin\Wechat\Handler\OfficialAccountHandler;
use Plugin\Wechat\Service\WechatSettingsResolver;
use Psr\Container\ContainerInterface;

class OfficialAccountFactory
{
    /**
     * 该函数用于将当前类实例作为一个可调用对象
     * 主要用于依赖注入容器，通过此函数可以创建并配置 MiniHandler 类的实例.
     *
     * @param ContainerInterface $container 依赖注入容器对象，用于获取配置信息
     * @return OfficialAccountHandler 返回一个 MiniHandler 实例，用于处理微信小程序相关的逻辑
     */
    public function __invoke(ContainerInterface $container)
    {
        $option = $container->get(WechatSettingsResolver::class)->toArray();

        // 使用依赖注入的方式创建并返回一个 MiniHandler 实例，传入微信配置选项作为构造函数参数
        return \Hyperf\Support\make(OfficialAccountHandler::class, [$option]);
    }
}
