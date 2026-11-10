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

use Plugin\SupplyChain\Domain\Entity\TaskEntity;

final class TaskMapper
{
    public static function getNewEntity(): TaskEntity
    {
        return new TaskEntity();
    }

    public static function fromModel(array $record): TaskEntity
    {
        return new TaskEntity($record);
    }
}
