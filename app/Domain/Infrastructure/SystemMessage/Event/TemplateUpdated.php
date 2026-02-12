<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\SystemMessage\Event;

use App\Infrastructure\Model\SystemMessage\MessageTemplate;

/**
 * 模板更新事件.
 */
class TemplateUpdated
{
    public function __construct(
        public readonly MessageTemplate $template,
        public readonly array $changes
    ) {}
}
