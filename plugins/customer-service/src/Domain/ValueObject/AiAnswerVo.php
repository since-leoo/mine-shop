<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\ValueObject;

final readonly class AiAnswerVo
{
    public function __construct(public string $action, public string $text = '', public string $reason = '', public array $evidenceIds = [], public int $tokens = 0)
    {
        if (!in_array($action, ['answer', 'clarify', 'handoff'], true)) {
            throw new \InvalidArgumentException('无效的 AI 回复动作');
        }
        if ($action !== 'handoff' && trim($text) === '') {
            throw new \InvalidArgumentException('AI 回复不能为空');
        }
    }
}
