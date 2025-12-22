<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\Since\SystemMessage\Event;

use Plugin\Since\SystemMessage\Model\MessageTemplate;

/**
 * 模板事件基类
 */
abstract class TemplateEvent
{
    public function __construct(
        public readonly MessageTemplate $template
    ) {}
}

/**
 * 模板创建事件
 */
class TemplateCreated extends TemplateEvent
{
}

/**
 * 模板更新事件
 */
class TemplateUpdated extends TemplateEvent
{
    public function __construct(
        MessageTemplate $template,
        public readonly array $changes
    ) {
        parent::__construct($template);
    }
}

/**
 * 模板删除事件
 */
class TemplateDeleted extends TemplateEvent
{
}