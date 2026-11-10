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

interface ApiLogRepositoryInterface
{
    /** Must durably commit the sanitized request intent before returning. */
    public function begin(array $intent): int|string;

    /** Append/finalize this attempt; an unfinished intent remains visible as unknown. */
    public function finish(int|string $id, array $outcome): void;
}
