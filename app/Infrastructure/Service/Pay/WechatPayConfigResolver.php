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

namespace App\Infrastructure\Service\Pay;

use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;

final class WechatPayConfigResolver
{
    /**
     * 将商城后台保存的微信支付配置转换为 yansongda/pay 配置。
     *
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public function resolve(array $config): array
    {
        $mchid = (string) ($config['mchid'] ?? $config['mch_id'] ?? '');
        $appId = (string) ($config['app_id'] ?? $config['mini_app_id'] ?? '');
        $privateKey = (string) ($config['private_key'] ?? $config['mch_secret_cert'] ?? '');
        $apiV3Key = (string) ($config['apiv3_key'] ?? $config['mch_secret_key'] ?? '');

        if ($mchid === '' || $appId === '' || $privateKey === '' || $apiV3Key === '') {
            throw new BusinessException(ResultCode::FAIL, '微信支付配置不完整');
        }

        return array_replace($config, [
            'app_id' => $appId,
            'mini_app_id' => $appId,
            'mch_id' => $mchid,
            'mch_secret_cert' => $privateKey,
            'mch_secret_key' => $apiV3Key,
        ]);
    }
}
