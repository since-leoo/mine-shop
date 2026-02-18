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

namespace Plugin\ExportCenter\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;

/**
 * 导出队列消费进程.
 *
 * 使用独立的 export 队列，handle_timeout 更长，适合大数据量导出任务。
 */
class ExportConsumerProcess extends ConsumerProcess
{
    protected string $pool = 'export';
}
