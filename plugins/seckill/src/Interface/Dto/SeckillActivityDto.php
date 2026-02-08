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

namespace Plugin\Since\Seckill\Interface\Dto;

use Plugin\Since\Seckill\Domain\Contract\SeckillActivityInput;

final class SeckillActivityDto implements SeckillActivityInput
{
    public ?int $id = null;

    public ?string $title = null;

    public ?string $description = null;

    public ?string $status = null;

    public ?array $rules = null;

    public ?string $remark = null;

    public function getId(): int
    {
        return $this->id ?? 0;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): ?string
    {
        return $this->status;
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
