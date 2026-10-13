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

use Plugin\Wechat\Handler\LazyMiniAppHandler;
use Plugin\Wechat\Handler\MiniAppAppHandler;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Plugin\Wechat\Service\WechatSettingsResolver;
use Psr\Container\ContainerInterface;

class MiniAppFactory
{
    public function __invoke(ContainerInterface $container): MiniAppInterface
    {
        // 会员中心也依赖此接口，构造依赖图时不应要求商家已经配置微信。
        return new LazyMiniAppHandler(static function () use ($container): MiniAppInterface {
            $option = $container->get(WechatSettingsResolver::class)->toArray();

            return \Hyperf\Support\make(MiniAppAppHandler::class, [$option]);
        });
    }
}
