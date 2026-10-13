<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Trade;

use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;
use Hyperf\DbConnection\Db;

final class HyperfTransactionManager implements TransactionManagerInterface
{
    public function run(callable $callback): mixed
    {
        return Db::transaction($callback);
    }
}
