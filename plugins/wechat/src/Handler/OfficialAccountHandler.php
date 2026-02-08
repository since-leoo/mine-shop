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

namespace Plugin\Wechat\Handler;

use EasyWeChat\Exceptions\InvalidArgumentException;
use EasyWeChat\OfficialAccount\Application;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use Plugin\Wechat\Construct\AbstractWechat;
use Plugin\Wechat\Interfaces\OfficialAccountInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;

class OfficialAccountHandler extends AbstractWechat implements OfficialAccountInterface
{
    /**
     * 创建授权链接.
     * @throws InvalidArgumentException
     */
    public function createAuthorizationUrl(string $redirectUrl = ''): string
    {
        return $this->app->getOAuth()->redirect($redirectUrl);
    }

    /**
     * 授权回调获取用户信息.
     * @throws InvalidArgumentException
     */
    public function getUserInfo(string $code): array
    {
        return $this->app->getOAuth()->userFromCode($code)->toArray();
    }

    /**
     * 初始化.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initInstance(): void
    {
        $this->app = make(Application::class, [$this->config]);

        // region 替换请求
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        $this->app->setRequest($request);

        // region 替换缓存
        $cache = ApplicationContext::getContainer()->get(CacheInterface::class);
        $this->app->setCache($cache);
    }
}
