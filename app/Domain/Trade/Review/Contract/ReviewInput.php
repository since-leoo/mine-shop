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

namespace App\Domain\Trade\Review\Contract;

/**
 * 评价输入契约.
 */
interface ReviewInput
{
    public function getId(): int;

    public function getOrderId(): ?int;

    public function getOrderItemId(): ?int;

    public function getProductId(): ?int;

    public function getSkuId(): ?int;

    public function getMemberId(): ?int;

    public function getRating(): ?int;

    public function getContent(): ?string;

    public function getImages(): ?array;

    public function getIsAnonymous(): ?bool;
}
