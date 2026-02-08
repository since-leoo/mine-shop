<?php

declare(strict_types=1);

namespace Plugin\Since\SystemMessage\Event;

use Plugin\Since\SystemMessage\Model\MessageTemplate;

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
