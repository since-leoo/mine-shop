<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Contract;

use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;

interface AiProviderInterface
{
    /** Implementations never choose a member identity or execute business writes. */
    public function answer(array $connection, array $agent, array $messages, array $evidence): AiAnswerVo;
}
