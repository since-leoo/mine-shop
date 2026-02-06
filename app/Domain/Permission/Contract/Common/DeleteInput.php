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

namespace App\Domain\Permission\Contract\Common;

/**
 * 删除操作输入契约.
 */
interface DeleteInput
{
    /**
     * 获取要删除的ID列表.
     * @return array<int>
     */
    public function getIds(): array;

    /**
     * 获取操作者ID.
     */
    public function getOperatorId(): int;
}
