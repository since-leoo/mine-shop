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

namespace Plugin\ExportCenter\Event;

use Plugin\ExportCenter\Model\ExportTask;

/**
 * 导出任务失败事件.
 */
class ExportTaskFailed
{
    public function __construct(
        public readonly ExportTask $task,
        public readonly string $errorMessage = ''
    ) {}
}
