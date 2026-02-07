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

namespace App\Domain\Organization\Contract\Leader;

/**
 * 创建领导操作输入契约.
 */
interface LeaderCreateInput
{
    /**
     * 获取部门ID.
     */
    public function getDeptId(): int;

    /**
     * 获取用户ID列表.
     * @return array<int>
     */
    public function getUserIds(): array;

    /**
     * 获取操作者ID.
     */
    public function getOperatorId(): int;

    /**
     * 转换为数组（用于简单 CRUD 操作）.
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
