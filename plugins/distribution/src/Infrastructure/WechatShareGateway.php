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

namespace Plugin\Distribution\Infrastructure;

use App\Infrastructure\Error\DistributionError;
use Plugin\Distribution\Domain\Contract\ShareLinkGatewayInterface;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\Container\ContainerInterface;

final class WechatShareGateway implements ShareLinkGatewayInterface
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function generate(string $channel, string $page, string $query): array
    {
        if (! $this->container->has(MiniAppInterface::class)) {
            throw DistributionError::WECHAT_PLUGIN_NOT_CONFIGURED->exception();
        }
        $wechat = $this->container->get(MiniAppInterface::class);
        $scene = '';
        if ($channel === 'qrcode') {
            parse_str($query, $parameters);
            $token = $parameters['share_token'] ?? '';
            if (! \is_string($token) || ! preg_match('/^[a-f0-9]{24}$/', $token)) {
                throw DistributionError::SHARE_TOKEN_INVALID->exception();
            }
            $scene = 's=' . $token;
        }
        $response = match ($channel) {
            'short_link' => $wechat->getMiniShortLink($page . '?' . $query, '邀请逛商城'),
            'url_link' => $wechat->getUrlLink($page, $query, expireType: 1, expireInterval: 30),
            'qrcode' => $wechat->getLimitedWxaCode('plugins/distribution/pages/landing/index', $scene, true),
            default => [],
        };
        if (! empty($response['errcode'])) {
            throw DistributionError::WECHAT_SHARE_FAILED->exception(
                [DistributionError::WECHAT_ACCOUNT_PERMISSION_REQUIRED->message()],
                new \RuntimeException((string) ($response['errmsg'] ?? ''), (int) $response['errcode']),
            );
        }
        $value = match ($channel) {
            'short_link' => $response['link'] ?? '', 'url_link' => $response['url_link'] ?? '', 'qrcode' => $response['path'] ?? '', default => ''
        };
        if ($value === '') {
            throw DistributionError::WECHAT_SHARE_RESULT_MISSING->exception();
        }
        if ($channel === 'qrcode') {
            $value = '/api/v1/distribution/share/images/' . basename($value);
        }
        return [$channel === 'qrcode' ? 'image_url' : 'url' => $value];
    }
}
