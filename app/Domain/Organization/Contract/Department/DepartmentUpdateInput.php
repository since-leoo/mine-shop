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

namespace App\Domain\Organization\Contract\Department;

/**
 * 更新部门操作输入契约.
 */
interface DepartmentUpdateInput
{
    /**
     * 获取部门ID.
     */
    public function getId(): int;

    /**
     * 获取部门名称.
     */
    public function getName(): string;

    /**
     * 获取上级部门ID.
     */
    public function getParentId(): ?int;

    /**
     * 获取部门用户ID列表.
     * @return array<int>
     */
    public function getDepartmentUsers(): array;

    /**
     * 获取领导用户ID列表.
     * @return array<int>
     */
    public function getLeaders(): array;

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
