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
 * 秒杀商品输入契约.
 */
interface SeckillProductInput
{
    public function getId(): int;

    public function getActivityId(): ?int;

    public function getSessionId(): ?int;

    public function getProductId(): ?int;

    public function getProductSkuId(): ?int;

    public function getOriginalPrice(): ?float;

    public function getSeckillPrice(): ?float;

    public function getQuantity(): ?int;

    public function getMaxQuantityPerUser(): ?int;

    public function getSortOrder(): ?int;
}
