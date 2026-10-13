<?php

declare(strict_types=1);

namespace App\Interface\Api\Request\V1\Seckill;

use App\Domain\Trade\Seckill\Contract\SeckillPromotionQueryInput;
use App\Interface\Api\Dto\Seckill\SeckillPromotionQueryDto;
use App\Interface\Common\Request\BaseRequest;

final class SeckillPromotionRequest extends BaseRequest
{
    public function indexRules(): array
    {
        return [
            'activity_id' => ['nullable', 'integer', 'min:1'],
            'promotion_id' => ['nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDto(): SeckillPromotionQueryInput
    {
        $data = $this->validated();
        $activityId = $data['activity_id'] ?? $data['promotion_id'] ?? null;
        return new SeckillPromotionQueryDto(
            $activityId !== null ? (int) $activityId : null,
            isset($data['session_id']) ? (int) $data['session_id'] : null,
            (int) ($data['limit'] ?? 20),
        );
    }
}
