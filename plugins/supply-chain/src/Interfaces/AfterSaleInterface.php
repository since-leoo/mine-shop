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

use Plugin\SupplyChain\Interfaces\Data\AfterSaleCommand;

interface AfterSaleInterface
{
    /** Query available remote refund types for this exact procurement order line. */
    public function types(string $externalOrderItemId): array;

    public function create(AfterSaleCommand $command): string;

    public function find(string $externalAfterSaleId): array;

    public function findByOrderItem(string $externalOrderItemId): array;

    public function close(string $externalAfterSaleId): void;

    public function returnAddress(string $merchantOrderNo): array;

    public function shipReturn(string $externalAfterSaleId, string $companyName, string $companyCode, string $trackingNo): void;
}
