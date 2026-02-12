<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\SystemMessage\Event;

use App\Infrastructure\Model\SystemMessage\MessageTemplate;

/**
 * 模板创建事件.
 */
class TemplateCreated
{
    public function __construct(
        public readonly MessageTemplate $template
    ) {}
}
