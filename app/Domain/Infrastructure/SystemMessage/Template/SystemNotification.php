<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\SystemMessage\Template;

use App\Domain\Infrastructure\SystemMessage\Contract\AbstractMessageTemplate;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;

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

    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getType(): MessageType { return MessageType::SYSTEM; }
    public function getPriority(): int { return $this->priority; }
    protected function recipients(): array { return $this->userIds; }
}
