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

namespace Plugin\SupplyChain\Interfaces\Data;

use App\Infrastructure\Error\SupplyChainError;

final readonly class PurchaseLine
{
    public function __construct(public string $externalSkuId, public int $quantity)
    {
        if ($externalSkuId === '' || $quantity < 1) {
            throw SupplyChainError::PURCHASE_LINE_INVALID->invalid();
        }
    }
}
