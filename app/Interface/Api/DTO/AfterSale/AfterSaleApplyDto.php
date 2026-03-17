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

namespace App\Interface\Api\DTO\AfterSale;

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;

class AfterSaleApplyDto implements AfterSaleApplyInput
{
    public int $order_id = 0;

    public int $order_item_id = 0;

    public int $member_id = 0;

    public string $type = 'refund_only';

    public string $reason = '';

    public ?string $description = null;

    public int $apply_amount = 0;

    public int $quantity = 1;

    public ?array $images = null;

    public function getOrderId(): int
    {
        return $this->order_id;
    }

    public function getOrderItemId(): int
    {
        return $this->order_item_id;
    }

    public function getMemberId(): int
    {
        return $this->member_id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getApplyAmount(): int
    {
        return $this->apply_amount;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }
}