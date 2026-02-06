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

namespace App\Domain\Permission\Event;

final class UserCreatedEvent
{
    /** @var int */
    public $userId;

    public function __construct($userId)
    {
        $this->userId = (int) $userId;
    }
}
