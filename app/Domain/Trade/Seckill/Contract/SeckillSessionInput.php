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

namespace App\Domain\Trade\Seckill\Contract;

interface SeckillSessionInput
{
    public function getId(): int;

    public function getActivityId(): ?int;

    public function getStartTime(): ?string;

    public function getEndTime(): ?string;

    public function getStatus(): ?string;

    public function getMaxQuantityPerUser(): ?int;

    public function getTotalQuantity(): ?int;

    public function getSortOrder(): ?int;

    public function getRules(): ?array;

    public function getRemark(): ?string;
}
