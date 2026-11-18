<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\ValueObject;

use App\Infrastructure\Error\CustomerServiceError;

final readonly class AiAnswerVo
{
    public function __construct(public string $action, public string $text = '', public string $reason = '', public array $evidenceIds = [], public int $tokens = 0)
    {
        if (!in_array($action, ['answer', 'clarify', 'handoff'], true)) {
            throw CustomerServiceError::AI_ANSWER_ACTION_INVALID->invalid();
        }
        if ($action !== 'handoff' && trim($text) === '') {
            throw CustomerServiceError::AI_ANSWER_EMPTY->invalid();
        }
    }
}
