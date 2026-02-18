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
