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

namespace App\Interface\Admin\DTO\Seckill;

use App\Domain\Seckill\Contract\SeckillSessionInput;

/**
 * 秒杀场次DTO.
 */
final class SeckillSessionDto implements SeckillSessionInput
{
    public ?int $id = null;

    public ?int $activity_id = null;

    public ?string $start_time = null;

    public ?string $end_time = null;

    public ?string $status = null;

    public ?int $max_quantity_per_user = null;

    public ?int $total_quantity = null;

    public ?int $sort_order = null;

    public ?array $rules = null;

    public ?string $remark = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getActivityId(): ?int
    {
        return $this->activity_id;
    }

    public function getStartTime(): ?string
    {
        return $this->start_time;
    }

    public function getEndTime(): ?string
    {
        return $this->end_time;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getMaxQuantityPerUser(): ?int
    {
        return $this->max_quantity_per_user;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->total_quantity;
    }

    public function getSortOrder(): ?int
    {
        return $this->sort_order;
    }

    public function getRules(): ?array
    {
        return $this->rules;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }
}
