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

namespace Plugin\Wechat\Test;

use Hyperf\Di\Annotation\Inject;
use Plugin\Wechat\Interfaces\OfficialAccountInterface;

class OfficialTest
{
    #[Inject]
    protected OfficialAccountInterface $app;

    /**
     * 获取授权地址.
     */
    public function createAuthorizationUrl(string $redirectUrl = ''): void
    {
        // 获取授权地址
        $response = $this->app->createAuthorizationUrl($redirectUrl);

        var_dump($response);
    }

    /**
     * 获取用户信息.
     */
    public function getUserInfo(string $code): void
    {
        $response = $this->app->getUserInfo($code);

        var_dump($response);
    }

    /**
     * 自定义方法.
     */
    public function other(): void
    {
        /*
         * 自定义方法,通过获取微信小程序实例，实现调用插件内未实现的微信接口,以下以获取客户端版本为例.
         */
        $response = $this->app->getClient()->get('/wxaapi/log/get_client_version')->toArray();

        /*
         * 自定义方法，通过传入闭包形式，获取内部app 实例，实现调用微信接口，可用于获取微信接口返回的完整响应信息.
         * Application $app 为 EasyWeChat\OfficialAccount\Application 工厂类.
         * someMethod 为 EasyWeChat\OfficialAccount\Application 的一个方法.
         */
        // 第一种，参数为一个闭包，它将在调用方法前打印一条消息
        // $response = $this->app->getHttpClient(function (Application $app) {
        // echo "Before calling method...\n";
        // // 这里可以访问和使用应用实例 $app
        // $result = $app->getClient()->get('/wxaapi/log/get_client_version')->toArray(); // 假设这是我们要调用的方法
        // echo "After calling method.\n";
        // return $result;
        // });

        // 第二种，参数为Client端的函数方法, 它将在调用时自动执行$this->app->getClient()->postJson方法
        // 具体可参考 https://easywechat.com/6.x/client.html
        // $response = $this->app->request('GET', '/wxaapi/log/get_client_version')->toArray();
        // 相当于 $app->getClient()->request('GET', '/api/data');

        var_dump($response);
    }
}
