<?php

declare(strict_types=1);

namespace App\Interface\Api\Dto\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillPromotionQueryInput;

final readonly class SeckillPromotionQueryDto implements SeckillPromotionQueryInput
{
    public function __construct(
        private ?int $activity_id = null,
        private ?int $session_id = null,
        private int $limit = 20,
    ) {}

    public function getActivityId(): ?int { return $this->activity_id; }

    public function getSessionId(): ?int { return $this->session_id; }

    public function getLimit(): int { return $this->limit; }
}
