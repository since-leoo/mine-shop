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

namespace Plugin\Since\Coupon;

use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use Plugin\Since\Coupon\Domain\Service\CouponServiceAdapter;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                CouponServiceInterface::class => CouponServiceAdapter::class,
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
