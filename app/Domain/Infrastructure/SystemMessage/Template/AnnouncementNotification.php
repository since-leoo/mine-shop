<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\SystemMessage\Template;

use App\Domain\Infrastructure\SystemMessage\Contract\AbstractMessageTemplate;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;

/**
 * 公告通知模板
 */
class AnnouncementNotification extends AbstractMessageTemplate
{
    public function __construct(
        protected string $title,
        protected string $content,
        protected bool $sendEmail = false
    ) {}

    public function getTitle(): string { return $this->title; }
    public function getContent(): string { return $this->content; }
    public function getType(): MessageType { return MessageType::ANNOUNCEMENT; }

    public function getChannels(): array
    {
        $channels = [MessageChannel::SOCKETIO];
        if ($this->sendEmail) { $channels[] = MessageChannel::EMAIL; }
        return $channels;
    }

    public function getPriority(): int { return 4; }
}
