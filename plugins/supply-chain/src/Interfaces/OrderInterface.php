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

use Plugin\SupplyChain\Interfaces\Data\PurchaseCommand;

interface OrderInterface
{
    /** @return array{external_order_numbers: list<string>} */
    public function submit(PurchaseCommand $command): array;

    /** @return array Normalized orders and line identifiers for reconciliation. */
    public function findByMerchantOrderNo(string $merchantOrderNo): array;

    /** Legacy procurement API expects the merchant number in the wire field order_sn. */
    public function logistics(string $merchantOrderNo): array;

    public function receive(string $merchantOrderNo): void;

    /** Fails explicitly when the provider has no verified purchasing-order cancellation API. */
    public function cancel(string $merchantOrderNo): void;
}
