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

namespace App\Domain\Order\Contract;

use App\Domain\Order\Entity\OrderEntity;

interface OrderTypeStrategyInterface
{
    public function type(): string;

    public function validate(OrderEntity $orderEntity): void;

    public function buildDraft(OrderEntity $orderEntity): OrderEntity;

    public function postCreate(OrderEntity $orderEntity): void;
}
