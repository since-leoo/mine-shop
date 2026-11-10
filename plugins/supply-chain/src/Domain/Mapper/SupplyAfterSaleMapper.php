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

namespace Plugin\SupplyChain\Domain\Mapper;

use Plugin\SupplyChain\Domain\Entity\SupplyAfterSaleEntity;

final class SupplyAfterSaleMapper
{
    public static function getNewEntity(): SupplyAfterSaleEntity
    {
        return new SupplyAfterSaleEntity();
    }

    public static function fromModel(array $row): SupplyAfterSaleEntity
    {
        return new SupplyAfterSaleEntity($row);
    }
}
