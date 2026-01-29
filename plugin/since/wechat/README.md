<h1 align="center">Easy Wechat</h1>

将 [yangyang/wechat](https://github.com/overtrue/easy-sms)
和 MineAdmin 进行了缝合。env 配置 sdk 参数。代码内直接使用

## 特点
微信小程序：

- 获取微信二维码

- 获取微信短链接

- 获取授权登陆

- 获取静默授权

- 获取手机号码

微信公众号（后续提供更多能力）：

- 获取授权链接

- 授权回调获取用户信息



## 使用

> 在env中配置好微信相关数据

## 复制以下配置到env中

```
# 微信
WECHAT_APPID=
WECHAT_SECRET=
WECHAT_TOKEN=
WECHAT_AES_KEY=
WECHAT_OAUTH_CALLBACK_URL=
WECHAT_HTTP_THROW_EXCEPTION=true
```

本插件提供demo案例：

```php
小程序部分：请参考：Plugin\Wechat\Test\MiniAppTest.php

公众号部分：请参考：Plugin\Wechat\Test\OfficialAccountTest.php
```

## 引入：

```php
引用小程序工厂类对象

use Plugin\Wechat\Interfaces\MiniAppInterface;

#[Inject]
    protected MiniAppInterface $app;

```

## 使用

## 小程序静默登陆：

```php
    /**
     * 静默登陆.
     */
    public function silentAuthorize(string $code)
    {
        $response = $this->app->silentAuthorize($code);

        var_dump($response);
    }
```

## 用户授权：

```php
    /**
     * 获取用户信息.
     */
    public function performSilentLogin(string $code, string $encryptedData, string $iv)
    {
        $response = $this->app->performSilentLogin($code, $encryptedData, $iv);

        var_dump($response);
    }
```

## 获取手机号码：

```php
    /**
     * 获取手机号码.
     */
    public function getPhoneNumber(string $code)
    {
        $response = $this->app->getPhoneNumber($code);

        var_dump($response);
    }
```

## 获取小程序码(有限制)：

```php
    /**
     * 获取小程序码(有限制).
     */
    public function getLimitedWxaCode()
    {
        $page = 'pages/index/index';
        $scene = '';
        $response = $this->app->getLimitedWxaCode($page, $scene);

        var_dump($response);
    }
```

## 获取微信短链接：

```php
    /**
     * 获取微信短链接.
     */
    public function getMiniShortLink(string $pageUrl, string $pageTitle = '', bool $isPermanent = false)
    {
        // 是否获取永久短链（注意：永久短链有限制，具体参考小程序 https://developers.weixin.qq.com/miniprogram/dev/OpenApiDoc/qrcode-link/short-link/generateShortLink.html）
        $response = $this->app->getMiniShortLink($pageUrl, $pageTitle, $isPermanent);

        var_dump($response);
    }
```

## 获取加密scheme

```php
    /**
     * 获取加密scheme.
     */
    public function getSchemeCode(string $page, string $scene, string $envVersion = 'release', int $expireTime = -1, int $expireType = 0, int $expireInterval = -1)
    {
        $response = $this->app->getSchemeCode($page, $scene, $envVersion, $expireTime, $expireType, $expireInterval);

        var_dump($response);
    }
```

## 查询加密scheme

```php
    /**
     * 查询加密scheme.
     */
    public function checkSchemeCode(string $scheme, int $queryType = 0)
    {
        $response = $this->app->checkSchemeCode($scheme, $queryType);

        var_dump($response);
    }
```

## 获取加密url链接

```php
    /**
     * 获取加密url链接.
     */
    public function getUrlLink(string $path, string $query = '', int $expireType = 0, int $expireTime = -1, int $expireInterval = -1, array $cloudBase = [])
    {
        $response = $this->app->getUrlLink($path, $query, $expireType, $expireTime, $expireInterval, $cloudBase);

        var_dump($response);
    }
```

## 查询加密url链接

```php
    /**
     * 查询加密url链接.
     */
    public function checkUrlLink(string $urlLink, int $queryType = 0)
    {
        $response = $this->app->checkUrlLink($urlLink, $queryType);

        var_dump($response);
    }
```

## 自定义方法

你可以根据场景的不同，通过获取微信小程序实例，实现调用插件内未实现的微信接口,以下以获取客户端版本为例.：

```php
        // 第一种
        $response = $this->app->getClient()->get('/wxaapi/log/get_client_version')->toArray();

        /*
         * 自定义方法，通过传入闭包形式，获取内部app 实例，实现调用微信接口，可用于获取微信接口返回的完整响应信息.
         * Application $app 为 EasyWeChat\MiniApp\Application 工厂类.
         * someMethod 为 EasyWeChat\MiniApp\Application 的一个方法.
         */
        // 第二种，参数为一个闭包，它将在调用方法前打印一条消息
         $response = $this->app->getHttpClient(function (Application $app) {
         echo "Before calling method...\n";
         // 这里可以访问和使用应用实例 $app
         $result = $app->getClient()->get('/wxaapi/log/get_client_version')->toArray(); // 假设这是我们要调用的方法
         echo "After calling method.\n";
         return $result;
         });
        
        // 第三种，参数为Client端的函数方法, 它将在调用时自动执行$this->app->getClient()->postJson方法
        // 具体可参考 https://easywechat.com/6.x/client.html
         $response = $this->app->request('GET', '/wxaapi/log/get_client_version')->toArray();
        // 相当于
         $this->app->getClient()->request('GET', '/api/data');
        

```
