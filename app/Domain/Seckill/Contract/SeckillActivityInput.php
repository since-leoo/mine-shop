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

namespace App\Domain\Seckill\Contract;

/**
 * 秒杀活动输入契约.
 */
interface SeckillActivityInput
{
    public function getId(): int;

    public function getTitle(): ?string;

    public function getDescription(): ?string;

    public function getStatus(): ?string;

    public function getRules(): ?array;

    public function getRemark(): ?string;
}
