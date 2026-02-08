<?php

declare(strict_types=1);

namespace Plugin\Since\SystemMessage\Event;

use Plugin\Since\SystemMessage\Model\MessageTemplate;

/**
 * 模板删除事件.
 */
class TemplateDeleted
{
    public function __construct(
        public readonly MessageTemplate $template
    ) {}
}
