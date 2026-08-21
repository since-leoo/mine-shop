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

namespace Plugin\Wechat\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;

final class WechatSettingsResolver
{
    private const SETTING_KEY = 'mall.integration.wechat_auth_config';

    public function __construct(private readonly DomainSystemSettingService $settingService) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $config = $this->settingService->get(self::SETTING_KEY, $this->defaultConfig());
        $config = \is_array($config) ? array_replace($this->defaultConfig(), $config) : $this->defaultConfig();
        $oauth = \is_array($config['oauth'] ?? null) ? $config['oauth'] : [];
        $config['oauth'] = array_replace($this->defaultConfig()['oauth'], $oauth, [
            'redirect_url' => (string) ($config['oauth_redirect_url'] ?? $oauth['redirect_url'] ?? ''),
        ]);

        if (trim((string) $config['app_id']) === '' || trim((string) $config['secret']) === '') {
            throw new \RuntimeException('请先在后台“系统集成”中配置微信授权 AppID 和 AppSecret');
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultConfig(): array
    {
        return [
            'app_id' => '',
            'secret' => '',
            'token' => '',
            'aes_key' => '',
            'use_stable_access_token' => true,
            'oauth' => [
                'scopes' => ['snsapi_userinfo'],
                'redirect_url' => '',
            ],
            'response_type' => 'array',
            'http' => [
                'throw' => true,
                'timeout' => 5.0,
                'retry' => true,
            ],
            'log' => [
                'level' => 'debug',
                'file' => BASE_PATH . '/runtime/logs/wechat.log',
            ],
        ];
    }
}
