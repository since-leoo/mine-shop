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

use Plugin\Wechat\Interfaces\MiniAppInterface;

/** 仅实际使用微信能力时解析配置并创建 SDK；配置和调用异常保持原样抛出。 */
final class LazyMiniAppHandler implements MiniAppInterface
{
    private ?MiniAppInterface $handler = null;

    /** @param \Closure(): MiniAppInterface $factory */
    public function __construct(private readonly \Closure $factory) {}

    /** 保留原 handler 提供的 SDK 自定义调用入口，同样延迟到调用时初始化。 */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->handler()->{$name}(...$arguments);
    }

    public function getClient(): \EasyWeChat\Kernel\HttpClient\AccessTokenAwareClient
    {
        return $this->handler()->getClient();
    }

    public function sendSubscribeMessage(string $openid, string $templateId, array $data, string $page = ''): array
    {
        return $this->handler()->sendSubscribeMessage($openid, $templateId, $data, $page);
    }

    // 保留原 MiniAppAppHandler 的默认值，避免省略参数的调用改变二维码类型。
    public function getLimitedWxaCode(string $page, string $scene, bool $isLimited = false, array $options = []): array
    {
        return $this->handler()->getLimitedWxaCode($page, $scene, $isLimited, $options);
    }

    public function getSchemeCode(string $page, string $scene, string $envVersion = 'release', int $expireTime = -1, int $expireType = 0, int $expireInterval = -1): array
    {
        return $this->handler()->getSchemeCode($page, $scene, $envVersion, $expireTime, $expireType, $expireInterval);
    }

    public function checkSchemeCode(string $scheme, int $queryType = 0): array
    {
        return $this->handler()->checkSchemeCode($scheme, $queryType);
    }

    public function getMiniShortLink(string $pageUrl, string $pageTitle = '', bool $isPermanent = false): array
    {
        return $this->handler()->getMiniShortLink($pageUrl, $pageTitle, $isPermanent);
    }

    public function performSilentLogin(string $code, string $encryptedData, string $iv): array
    {
        return $this->handler()->performSilentLogin($code, $encryptedData, $iv);
    }

    public function getPhoneNumber(string $code): array
    {
        return $this->handler()->getPhoneNumber($code);
    }

    public function silentAuthorize(string $code): array
    {
        return $this->handler()->silentAuthorize($code);
    }

    public function getUrlLink(string $path, string $query = '', int $expireType = 0, int $expireTime = -1, int $expireInterval = -1, array $cloudBase = []): array
    {
        return $this->handler()->getUrlLink($path, $query, $expireType, $expireTime, $expireInterval, $cloudBase);
    }

    public function checkUrlLink(string $urlLink, int $queryType = 0): array
    {
        return $this->handler()->checkUrlLink($urlLink, $queryType);
    }

    private function handler(): MiniAppInterface
    {
        // 只缓存成功的实例；配置缺失后的再次调用仍可读取商家补全的配置。
        return $this->handler ??= ($this->factory)();
    }
}
