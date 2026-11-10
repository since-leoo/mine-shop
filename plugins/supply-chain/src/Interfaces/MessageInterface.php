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

interface MessageInterface
{
    /** @return list<array{external_message_id: string, type: string, payload: array}> */
    public function pull(int $pageSize = 100): array;

    /** Only call after inbox persistence commits. */
    public function acknowledge(array $externalMessageIds): void;
}
