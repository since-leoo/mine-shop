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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yansongda\Artful\Exception\ContainerException;
use Yansongda\Pay\Pay;

class YsdPayService
{
    public function pay(array $params, array $config): array
    {
        Pay::config(array_merge($config, ['_force' => true]));

        return Pay::wechat()->mini($params)->toArray();
    }

    public function refund(array $params, array $config, string $scene = 'mini'): array
    {
        Pay::config(array_merge($config, ['_force' => true]));

        return Pay::wechat()->refund(array_merge($params, ['_action' => $scene]))->toArray();
    }

    public function callback(ServerRequestInterface $request, array $config): array
    {
        Pay::config(array_merge($config, ['_force' => true]));

        return Pay::wechat()->callback($request)->toArray();
    }

    public function success(array $config): ResponseInterface
    {
        Pay::config(array_merge($config, ['_force' => true]));

        return Pay::wechat()->success();
    }
}
