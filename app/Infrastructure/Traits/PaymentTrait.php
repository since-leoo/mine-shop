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

namespace App\Infrastructure\Traits;

trait PaymentTrait
{
    protected static function build(): array
    {
        return [
            'out_trade_no' => time() . '',
            'description' => 'subject-测试',
            'amount' => [
                'total' => 1,
            ],
            'payer' => [
                'openid' => 'onkVf1FjWS5SBxxxxxxxx',
            ],
        ];
    }
}
