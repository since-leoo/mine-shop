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

namespace App\Application\SystemSetting\Assembler;

use App\Domain\SystemSetting\Entity\SystemSettingEntity;

final class SystemSettingAssembler
{
    public function fromRequest(string $key, mixed $value): SystemSettingEntity
    {
        return (new SystemSettingEntity())
            ->setKey($key)
            ->setValue($value);
    }
}
