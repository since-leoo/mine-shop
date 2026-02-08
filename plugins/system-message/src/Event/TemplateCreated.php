<?php

declare(strict_types=1);

namespace Plugin\Since\SystemMessage\Event;

use Plugin\Since\SystemMessage\Model\MessageTemplate;

/**
 * 模板创建事件.
 */
class TemplateCreated
{
    public function __construct(
        public readonly MessageTemplate $template
    ) {}
}
