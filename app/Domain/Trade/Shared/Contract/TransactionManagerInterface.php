<?php

declare(strict_types=1);

namespace App\Domain\Trade\Shared\Contract;

interface TransactionManagerInterface
{
    public function run(callable $callback): mixed;
}
