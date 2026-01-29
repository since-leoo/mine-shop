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

namespace Plugin\Wechat\Construct;

use EasyWeChat\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\HttpClient\AccessTokenAwareClient;
use EasyWeChat\MiniApp\Application as MiniApplication;
use EasyWeChat\OfficialAccount\Application as OfficialApplication;
use EasyWeChat\OfficialAccount\Server;
use EasyWeChat\OpenPlatform\Application as OpenPlatformApplication;

abstract class AbstractWechat
{
    // 配置数组
    protected array $config = [];

    // 应用实例，可以是MiniApplication、OfficialApplication或OpenPlatformApplication类型
    protected MiniApplication|OfficialApplication|OpenPlatformApplication $app;

    /**
     * 构造函数，用于初始化类实例.
     *
     * @param array $config 配置数组，用于设置应用的相关配置
     * @throws InvalidArgumentException 如果配置信息不正确或缺失必要配置，将抛出此异常
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        // 初始化应用实例
        $this->initInstance();
    }

    /**
     * 魔术方法，用于实现应用实例的动态调用.
     * @return mixed
     */
    public function __call(mixed $name, mixed $arguments)
    {
        // 如果参数是闭包，则直接调用闭包并传入应用实例
        if ($arguments && $arguments[0] instanceof \Closure) {
            // 调用闭包并传入应用实例
            return \call_user_func($arguments[0], $this->app);
        }
        return $this->getClient()->{$name}(...$arguments);
    }

    /**
     * 获取服务器实例.
     *
     * @return \EasyWeChat\Kernel\Contracts\Server|\EasyWeChat\MiniApp\Server|\EasyWeChat\OpenPlatform\Server|Server 服务器实例
     * @throws InvalidArgumentException 如果应用配置不正确，将抛出此异常
     * @throws \ReflectionException 如果反射操作失败，将抛出此异常
     * @throws \Throwable 其他未知错误
     */
    public function getServer(): \EasyWeChat\Kernel\Contracts\Server|\EasyWeChat\MiniApp\Server|\EasyWeChat\OpenPlatform\Server|Server
    {
        return $this->app->getServer();
    }

    /**
     * 获取客户端实例.
     */
    public function getClient(): AccessTokenAwareClient
    {
        return $this->app->getClient();
    }

    /**
     * 初始化应用实例的具体实现
     * 该方法应在子类中实现，用于完成应用实例的初始化操作.
     *
     * @throws InvalidArgumentException 如果应用配置不正确，将抛出此异常
     */
    abstract protected function initInstance();
}
