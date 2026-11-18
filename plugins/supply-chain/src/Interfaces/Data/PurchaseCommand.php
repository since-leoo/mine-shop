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

final readonly class PurchaseCommand extends CheckoutCommand
{
    public function __construct(
        public string $merchantOrderNo,
        array $lines,
        Address $address,
        public string $remark = '',
    ) {
        parent::__construct($lines, $address);
        if ($merchantOrderNo === '' || mb_strlen($merchantOrderNo) > 128) {
            throw SupplyChainError::PURCHASE_NUMBER_INVALID->invalid();
        }
    }
}
