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

use Plugin\Distribution\Domain\Entity\CommissionAccountEntity;

final class CommissionAccountMapper
{
    public static function fromModel(array $row): CommissionAccountEntity
    {
        return new CommissionAccountEntity(array_map('intval', array_intersect_key($row, array_flip(['member_id', 'pending_amount', 'available_amount', 'frozen_amount', 'withdrawn_amount', 'debt_amount']))));
    }
}
