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
 * 提醒通知模板
 */
class ReminderNotification extends AbstractMessageTemplate
{
    public function __construct(
        protected string $title,
        protected string $content,
        protected int $userId,
        protected array $extra = []
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
        return MessageType::REMINDER;
    }

    public function getExtra(): array
    {
        return $this->extra;
    }

    public function getPriority(): int
    {
        return 2;
    }

    protected function recipients(): array
    {
        return [$this->userId];
    }
}
