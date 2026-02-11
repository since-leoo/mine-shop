<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\SystemMessage\Template;

use App\Domain\Infrastructure\SystemMessage\Contract\AbstractMessageTemplate;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;

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

    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getType(): MessageType { return MessageType::REMINDER; }
    public function getExtra(): array { return $this->extra; }
    public function getPriority(): int { return 2; }
    protected function recipients(): array { return [$this->userId]; }
}
