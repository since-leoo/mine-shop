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

namespace App\Domain\Trade\AfterSale\Contract;

interface AfterSaleApplyInput
{
    public function getOrderId(): int;

    public function getOrderItemId(): int;

    public function getMemberId(): int;

    public function getType(): string;

    public function getReason(): string;

    public function getDescription(): ?string;

    public function getApplyAmount(): int;

    public function getQuantity(): int;

    public function getImages(): ?array;
}
