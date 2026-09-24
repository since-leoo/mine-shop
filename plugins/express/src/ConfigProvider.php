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

namespace Plugin\Express;

use Plugin\Express\Contract\LogisticsTrackingInterface;
use Plugin\Express\Service\ExpressTrackingService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'listeners' => [],
            'dependencies' => [
                LogisticsTrackingInterface::class => ExpressTrackingService::class,
            ],
            'mall' => ['groups' => Plugin::mallGroups()],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
