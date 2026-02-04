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

use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;

final class YsdPayService
{
    /**
     * @throws ContainerException
     */
    public function pay(array $params, array $config): array
    {
        // 合并配置
        Pay::config(array_merge($config, ['_force' => true]));

        return Pay::wechat()->mini($params)->toArray();
    }
}
