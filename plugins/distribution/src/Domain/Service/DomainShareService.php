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

namespace Plugin\Distribution\Domain\Service;

use App\Infrastructure\Error\DistributionError;
use Plugin\Distribution\Domain\Contract\ShareInput;
use Plugin\Distribution\Domain\Contract\ShareLinkGatewayInterface;
use Plugin\Distribution\Domain\Repository\DistributionRepository;

final class DomainShareService
{
    public function __construct(private readonly DistributionRepository $repository, private readonly ShareLinkGatewayInterface $links) {}

    public function create(int $memberId, ShareInput $input): array
    {
        $data = $input->toArray();
        $settings = $this->repository->settings()['settings'];
        $member = $this->repository->member($memberId);
        if (! $member || $member['status'] !== 'active') {
            throw DistributionError::MEMBER_SHARE_FORBIDDEN->exception();
        }
        $target = $data['target'];
        $page = $target === 'product' ? 'pages/goods/details/index' : 'pages/home/index';
        $query = [];
        if ($target === 'product') {
            if (empty($data['spu_id'])) {
                throw DistributionError::SHARE_PRODUCT_REQUIRED->exception();
            }
            $query['spuId'] = (int) $data['spu_id'];
            foreach (['sku_id' => 'skuId', 'activity_id' => 'activityId', 'order_type' => 'orderType', 'session_id' => 'sessionId', 'group_no' => 'groupNo', 'group_buy_id' => 'groupBuyId'] as $key => $param) {
                if (isset($data[$key]) && $data[$key] !== '') {
                    $query[$param] = $data[$key];
                }
            }
        }
        $enabled = (bool) $settings['enabled'];
        $token = '';
        $expires = null;
        if ($enabled) {
            $share = $this->repository->createShare($memberId, $data['channel'], ['target' => $target, 'path' => '/' . $page, 'query' => $query, 'spu_id' => $data['spu_id'] ?? null]);
            $token = $share['token'];
            $expires = $share['expires_at'];
            $query['share_token'] = $token;
        }
        $qs = http_build_query($query, '', '&', \PHP_QUERY_RFC3986);
        $path = '/' . $page . ($qs !== '' ? '?' . $qs : '');
        $base = rtrim($settings['h5_base_url'], '/');
        $result = ['enabled' => $enabled, 'reason' => $enabled ? 'ok' : 'disabled', 'share_token' => $token, 'path' => $path, 'h5_url' => $base !== '' ? $base . '/#' . $path : '', 'expires_at' => $expires];
        if ($enabled && \in_array($data['channel'], ['short_link', 'url_link', 'qrcode'], true) && ! ($data['channel'] === 'qrcode' && ($data['platform'] ?? '') === 'h5')) {
            $result += $this->links->generate($data['channel'], $page, $qs);
        }
        return $result;
    }
}
