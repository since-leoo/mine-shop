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

use Hyperf\AsyncQueue\Process\ConsumerProcess;

final class AfterSaleConsumerProcess extends ConsumerProcess
{
    protected string $pool = 'supply_after_sale';
}
