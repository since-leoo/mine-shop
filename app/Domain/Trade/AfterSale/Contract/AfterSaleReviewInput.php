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

interface AfterSaleReviewInput
{
    public function getId(): int;

    public function getApprovedRefundAmount(): ?int;

    public function getRejectReason(): string;

    public function getRemark(): string;

    public function getOperatorId(): int;

    public function getOperatorName(): string;
}