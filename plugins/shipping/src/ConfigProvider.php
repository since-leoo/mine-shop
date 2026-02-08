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

namespace Plugin\Since\Shipping;

use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use Plugin\Since\Shipping\Domain\Service\FreightServiceAdapter;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                FreightServiceInterface::class => FreightServiceAdapter::class,
            ],
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
