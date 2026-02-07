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

namespace App\Domain\SystemSetting\Contract;

/**
 * 系统设置输入契约接口.
 */
interface SystemSettingInput
{
    public function getKey(): string;

    public function getValue(): mixed;

    public function getType(): string;
}
