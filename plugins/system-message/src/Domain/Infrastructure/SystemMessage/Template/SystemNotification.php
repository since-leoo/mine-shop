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

namespace Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Template;

use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\AbstractMessageTemplate;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Enum\MessageType;

/**
 * 系统通知模板
 */
class SystemNotification extends AbstractMessageTemplate
{
    public function __construct(
        protected string $title,
        protected string $content,
        protected array $userIds = [],
        protected int $priority = 3
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): MessageType
    {
        return MessageType::SYSTEM;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    protected function recipients(): array
    {
        return $this->userIds;
    }
}
