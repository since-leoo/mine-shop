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

interface SupplyChainManagerInterface
{
    /** Each call resolves current settings; clients are immutable connection snapshots. */
    public function forConnection(int $connectionId): SupplyChainClientInterface;
}
