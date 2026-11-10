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

readonly class CheckoutCommand
{
    /** @param list<PurchaseLine> $lines */
    public function __construct(public array $lines, public Address $address)
    {
        if ($lines === [] || \count($lines) > 100) {
            throw new \InvalidArgumentException('供应链采购明细数量无效');
        }
        foreach ($lines as $line) {
            if (! $line instanceof PurchaseLine) {
                throw new \InvalidArgumentException('供应链采购明细类型无效');
            }
        }
    }
}
