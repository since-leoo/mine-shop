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

namespace Plugin\Distribution\Domain\Mapper;

use Plugin\Distribution\Domain\Entity\WithdrawalEntity;

final class WithdrawalMapper
{
    public static function getNewEntity(): WithdrawalEntity
    {
        return new WithdrawalEntity();
    }

    public static function fromModel(array $row): WithdrawalEntity
    {
        return new WithdrawalEntity($row);
    }
}
