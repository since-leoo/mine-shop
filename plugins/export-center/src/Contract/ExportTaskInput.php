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

namespace Plugin\ExportCenter\Contract;

/**
 * 导出任务输入契约接口.
 */
interface ExportTaskInput
{
    /**
     * 获取用户ID.
     */
    public function getUserId(): int;

    /**
     * 获取任务名称.
     */
    public function getTaskName(): string;

    /**
     * 获取 DTO 类名（带 ExportColumn 注解的类）.
     */
    public function getDtoClass(): string;

    /**
     * 获取导出格式 (excel|csv).
     */
    public function getExportFormat(): string;

    /**
     * 获取导出参数.
     */
    public function getExportParams(): array;
}
