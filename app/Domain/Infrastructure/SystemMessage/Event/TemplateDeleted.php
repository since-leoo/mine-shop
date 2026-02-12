<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\SystemMessage\Event;

use App\Infrastructure\Model\SystemMessage\MessageTemplate;

/**
 * 模板删除事件.
 */
class TemplateDeleted
{
    public function __construct(
        public readonly MessageTemplate $template
    ) {}
}
