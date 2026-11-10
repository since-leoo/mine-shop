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

namespace Plugin\SupplyChain\Interfaces;

interface RequestLimiterInterface
{
    /** Atomically admit across workers and nodes; fail fast instead of blocking a worker slot. */
    public function acquire(int $connectionId, int $leaseSeconds): string;

    public function release(int $connectionId, string $lease): void;
}
