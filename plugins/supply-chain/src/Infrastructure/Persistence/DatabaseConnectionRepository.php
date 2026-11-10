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

namespace Plugin\SupplyChain\Infrastructure\Persistence;

use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Interfaces\ConnectionRepositoryInterface;

final class DatabaseConnectionRepository implements ConnectionRepositoryInterface
{
    public function __construct(private readonly SupplyRepository $repository) {}

    public function get(int $connectionId): array
    {
        return $this->repository->require('connections', $connectionId);
    }
}
