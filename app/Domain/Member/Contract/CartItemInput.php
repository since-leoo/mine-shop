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

namespace App\Domain\Member\Contract;

/**
 * 购物车条目输入契约.
 */
interface CartItemInput
{
    public function getSkuId(): int;

    public function getQuantity(): ?int;
}
