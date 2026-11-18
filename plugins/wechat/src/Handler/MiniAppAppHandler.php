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

use App\Infrastructure\Error\WechatError;
use EasyWeChat\Kernel\Exceptions\BadResponseException;

use EasyWeChat\MiniApp\Application;
use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use Plugin\Wechat\Construct\AbstractWechat;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class MiniAppAppHandler extends AbstractWechat implements MiniAppInterface
{
    public function sendSubscribeMessage(string $openid, string $templateId, array $data, string $page = ''): array
    {
        $payload = [
            'touser' => $openid,
            'template_id' => $templateId,
            'data' => $data,
        ];
        if ($page !== '') {
            $payload['page'] = $page;
        }

        return $this->getClient()->postJson('/cgi-bin/message/subscribe/send', $payload)->toArray();
    }

    /**
     * 获取授权登陆.
     */
    public function performSilentLogin(string $code, string $encryptedData, string $iv): array
    {
        $utils = $this->app->getUtils();

        $session = $utils->codeToSession($code);

        return $utils->decryptSession($session['session_key'], $iv, $encryptedData);
    }

    /**
     * 获取手机号码.
     */
    public function getPhoneNumber(string $code): array
    {
        return $this->getClient()->postJson('/wxa/business/getuserphonenumber', ['code' => $code])->toArray();
    }

    /**
     * 静默授权.
     */
    public function silentAuthorize(string $code): array
    {
        return $this->app->getUtils()->codeToSession($code);
    }

    /**
     * 获取小程序码(有限制).
     * @param string $page 小程序页面路径
     * @param string $scene 场景值，可以是任意字符串
     * @param bool $isLimited 是否使用无限制的小程序码，默认为false
     * @param array $options 配置项
     * @return array|string[]
     */
    public function getLimitedWxaCode(string $page, string $scene, bool $isLimited = false, array $options = []): array
    {
        $url = $isLimited ? '/wxa/getwxacodeunlimit' : '/wxa/getwxacode';
        $params = [
            'width' => $options['width'] ?? 430,
            'auto_color' => $options['auto_color'] ?? false,
            'line_color' => $options['line_color'] ?? ['r' => 0, 'g' => 0, 'b' => 0],
        ];
        if ($isLimited) {
            $params += ['page' => ltrim($page, '/'), 'scene' => $scene, 'check_path' => $options['check_path'] ?? true,
                'is_hyaline' => $options['is_hyaline'] ?? false, 'env_version' => $options['env_version'] ?? 'release'];
        } else {
            $params['path'] = ltrim($page, '/') . ($scene !== '' ? (str_contains($page, '?') ? '&' : '?') . $scene : '');
        }
        $response = $this->getClient()->postJson($url, $params);
        $contentType = strtolower(implode(';', $response->getHeaders(false)['content-type'] ?? []));
        if (!str_contains($contentType, 'image/')) {
            $error = $response->toArray(false);
            return ['path' => '', 'errcode' => $error['errcode'] ?? -1, 'errmsg' => $error['errmsg'] ?? '微信未返回图片'];
        }
        $relativeDirectory = '/uploadfile/wechat/mini/qrcode/';
        $directory = BASE_PATH . '/public' . $relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw WechatError::CODE_DIRECTORY_CREATE_FAILED->internal();
        }
        $filename = hash('sha256', json_encode($params, JSON_THROW_ON_ERROR)) . '.png';
        file_put_contents($directory . $filename, $response->getContent());
        return ['path' => $relativeDirectory . $filename, 'name' => $filename, 'msg' => '成功'];
    }

    /**
     * 获取微信短链接.
     */
    public function getMiniShortLink(string $pageUrl, string $pageTitle = '', bool $isPermanent = false): array
    {
        $params = [
            'page_url' => $pageUrl,
            'page_title' => $pageTitle,
            'is_permanent' => $isPermanent,
        ];
        return $this->getClient()->postJson('/wxa/genwxashortlink', $params)->toArray();
    }

    /**
     * 获取加密scheme.
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getSchemeCode(string $page, string $scene, string $envVersion = 'release', int $expireTime = -1, int $expireType = 0, int $expireInterval = -1): array
    {
        $params = [
            'jump_wxa' => [
                'path' => $page,
                'query' => $scene,
                'env_version' => $envVersion,
            ],
            'expire_type' => $expireType,
        ];
        $expireTime !== -1 && $params['expire_time'] = $expireTime;
        $expireInterval !== -1 && $params['expire_interval'] = $expireInterval;

        return $this->getClient()->postJson('/wxa/generatescheme', $params)->toArray();
    }

    /**
     * 检查scheme.
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function checkSchemeCode(string $scheme, int $queryType = 0): array
    {
        $params = ['scheme' => $scheme, 'query_type' => $queryType];

        return $this->getClient()->postJson('/wxa/queryscheme', $params)->toArray();
    }

    /**
     * 获取小程序URL链接.
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getUrlLink(string $path, string $query = '', int $expireType = 0, int $expireTime = -1, int $expireInterval = -1, array $cloudBase = []): array
    {
        $params = [
            'path' => $path,
            'query' => $query,
            'expire_type' => $expireType,
        ];

        $expireTime !== -1 && $params['expire_time'] = $expireTime;
        $expireInterval !== -1 && $params['expire_interval'] = $expireInterval;
        ! \is_array($cloudBase) && $params['cloud_base'] = $cloudBase;

        return $this->getClient()->postJson('/wxa/generate_urllink', $params)->toArray();
    }

    /**
     * 检查小程序URL链接.
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function checkUrlLink(string $urlLink, int $queryType = 0): array
    {
        $params = ['url_link' => $urlLink, 'query_type' => $queryType];

        return $this->getClient()->postJson('/wxa/query_urllink', $params)->toArray();
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
