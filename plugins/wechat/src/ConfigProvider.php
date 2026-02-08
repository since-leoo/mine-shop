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

namespace Plugin\Wechat;

use Plugin\Wechat\Factory\MiniAppFactory;
use Plugin\Wechat\Factory\OfficialAccountFactory;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Plugin\Wechat\Interfaces\OfficialAccountInterface;

class ConfigProvider
{
    public function __invoke()
    {
        // Initial configuration
        return [
            // 合并到  config/autoload/annotations.php 文件
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'dependencies' => [
                MiniAppInterface::class => MiniAppFactory::class,
                OfficialAccountInterface::class => OfficialAccountFactory::class,
            ],
        ];
    }
}
