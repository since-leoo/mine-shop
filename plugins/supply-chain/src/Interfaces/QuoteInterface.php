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

namespace Plugin\SupplyChain\Interfaces;

use Plugin\SupplyChain\Interfaces\Data\CheckoutCommand;

interface QuoteInterface
{
    /** A rejected remote validation throws DomainException; transport failures never mean available. */
    public function assertAvailable(CheckoutCommand $command): void;

    /** @return array{freight_cents: int, checked_at: string} */
    public function quote(CheckoutCommand $command): array;
}
