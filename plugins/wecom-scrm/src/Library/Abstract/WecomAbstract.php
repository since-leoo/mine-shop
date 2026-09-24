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

namespace Plugin\WecomScrm\Library\Abstract;

/*
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Di\Container;
use Hyperf\Guzzle\ClientFactory;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

abstract class WecomAbstract
{
    protected string $domainUrl = 'https://qyapi.weixin.qq.com/cgi-bin';

    protected string $token = '';

    protected string $secret = '';

    protected string $corpId = '';

    protected string $corpSecret = '';

    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->getConfig();
    }

    abstract public function result(array $data): array;

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    protected function request(string $url, string $method = 'POST', array $params = []): array
    {
        $options = [
            'query' => [
                'access_token' => $this->getAccessToken(),
            ],
            'http_errors' => false,
            'timeout' => 10,
        ];
        if (mb_strtoupper($method) === 'GET') {
            $options['query'] += $params;
        } else {
            $options['json'] = $params;
        }
        $response = $this->container->get(ClientFactory::class)->create()
            ->request(
                $method,
                rtrim($this->domainUrl, '/') . '/' . ltrim($url, '/'),
                $options
            );
        $data = json_decode((string) $response->getBody(), true);
        if (! \is_array($data) || (int) ($data['errcode'] ?? -1) !== 0) {
            throw new \RuntimeException('企业微信 API 调用失败：' . ($data['errmsg'] ?? 'invalid response'));
        }
        return $this->result($data);
    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    private function getAccessToken(): string
    {
        $cache = $this->container->get(CacheInterface::class);
        $key = 'wecom:scrm:access_token:' . md5($this->corpId . $this->corpSecret);
        $cached = $cache->get($key);
        if (\is_string($cached) && $cached !== '') {
            return $cached;
        }
        $client = $this->container->get(ClientFactory::class)->create();
        $response = $client->get($this->domainUrl . '/gettoken', [
            'query' => [
                'corpid' => $this->corpId,
                'corpsecret' => $this->corpSecret,
            ],
            'http_errors' => false,
            'timeout' => 8,
        ]);
        $data = json_decode((string) $response->getBody(), true);
        if (! \is_array($data) || (int) ($data['errcode'] ?? -1) !== 0 || empty($data['access_token'])) {
            throw new \RuntimeException('企业微信 AccessToken 获取失败：' . ($data['errmsg'] ?? 'unknown'));
        }
        $cache->set($key, $data['access_token'], max(60, (int) ($data['expires_in'] ?? 7200) - 300));
        return (string) $data['access_token'];
    }

    private function getConfig(): void
    {
        $this->corpId = (string) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.corp_id', '');
        $this->corpSecret = (string) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.secret', '');
        $this->token = (string) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.token', '');
        $this->secret = (string) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.aes_key', '');
    }
}
