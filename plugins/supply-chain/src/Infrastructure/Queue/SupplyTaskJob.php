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

namespace Plugin\SupplyChain\Infrastructure\Queue;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\SupplyChain\Application\Commad\AppSupplyTaskRunner;

final class SupplyTaskJob extends Job
{
    public int $maxAttempts = 0;

    public function __construct(public int $taskId, public int $dispatchVersion) {}

    public function handle(): void
    {
        ApplicationContext::getContainer()->get(AppSupplyTaskRunner::class)->run($this->taskId, $this->dispatchVersion);
    }
}
