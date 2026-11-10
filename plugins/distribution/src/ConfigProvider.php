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

namespace Plugin\Distribution;

use Plugin\Distribution\Domain\Contract\ShareLinkGatewayInterface;
use Plugin\Distribution\Infrastructure\WechatShareGateway;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => ['scan' => ['paths' => [__DIR__]]],
            'dependencies' => [ShareLinkGatewayInterface::class => WechatShareGateway::class],
        ];
    }
}
