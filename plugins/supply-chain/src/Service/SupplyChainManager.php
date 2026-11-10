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

namespace Plugin\SupplyChain\Service;

use Plugin\SupplyChain\Interfaces\SupplyChainClientInterface;
use Plugin\SupplyChain\Interfaces\SupplyChainManagerInterface;

final class SupplyChainManager implements SupplyChainManagerInterface
{
    /** @param \Closure(ConnectionConfig): SupplyChainClientInterface $createClient */
    public function __construct(private readonly SupplyChainSettingsResolver $settings, private readonly \Closure $createClient) {}

    public function forConnection(int $connectionId): SupplyChainClientInterface
    {
        // No process-wide mutable "current connection", and no stale permanent client cache.
        return ($this->createClient)($this->settings->resolve($connectionId));
    }
}
