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

namespace App\Interface\Api\DTO\Cart;

use App\Domain\Member\Contract\CartItemInput;

/**
 * 购物车条目 DTO.
 */
class CartItemDto implements CartItemInput
{
    public int $sku_id = 0;

    public int $quantity = 1;

    public ?bool $is_selected = null;

    public function getSkuId(): int
    {
        return $this->sku_id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getIsSelected(): ?bool
    {
        return $this->is_selected;
    }
}
