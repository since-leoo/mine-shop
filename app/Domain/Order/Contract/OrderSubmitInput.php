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

namespace App\Domain\Order\Contract;

/**
 * 订单提交输入契约接口.
 */
interface OrderSubmitInput extends OrderPreviewInput
{
    /**
     * 前端传入的总金额（分）.
     */
    public function getTotalAmount(): int;

    public function getUserName(): ?string;
}
