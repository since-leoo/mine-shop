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

namespace App\Domain\Trade\Order\Event;

/** Synchronous extension point before the submission payload becomes durable. */
final class OrderSubmissionPreparing
{
    public function __construct(public readonly int $memberId, public array $payload) {}
}
