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

namespace App\Interface\Api\DTO\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillPromotionQueryInput;

final readonly class SeckillPromotionQueryDto implements SeckillPromotionQueryInput
{
    public function __construct(
        private ?int $activity_id = null,
        private ?int $session_id = null,
        private int $limit = 20,
    ) {}

    public function getActivityId(): ?int
    {
        return $this->activity_id;
    }

    public function getSessionId(): ?int
    {
        return $this->session_id;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
