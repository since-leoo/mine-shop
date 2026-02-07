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

namespace App\Interface\Api\DTO\Order;

use App\Domain\Order\Contract\OrderSubmitInput;

/**
 * 订单提交 DTO.
 */
class OrderCommitDto extends OrderPreviewDto implements OrderSubmitInput
{
    public int $total_amount = 0;

    public ?string $user_name = null;

    /**
     * 前端传入的总金额（分）.
     */
    public function getTotalAmount(): int
    {
        return $this->total_amount;
    }

    public function getUserName(): ?string
    {
        return $this->user_name;
    }
}
