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

use App\Domain\Trade\AfterSale\Contract\AfterSaleReturnShipmentInput;

final class AfterSaleReturnShipmentDto implements AfterSaleReturnShipmentInput
{
    public int $id = 0;

    public int $member_id = 0;

    public string $logistics_company = '';

    public string $logistics_no = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getMemberId(): int
    {
        return $this->member_id;
    }

    public function getLogisticsCompany(): string
    {
        return $this->logistics_company;
    }

    public function getLogisticsNo(): string
    {
        return $this->logistics_no;
    }
}
