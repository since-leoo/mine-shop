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

namespace App\Domain\Trade\AfterSale\Contract;

interface AfterSaleReturnShipmentInput
{
    public function getId(): int;

    public function getMemberId(): int;

    public function getLogisticsCompany(): string;

    public function getLogisticsNo(): string;
}
