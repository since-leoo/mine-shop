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

namespace App\Domain\Infrastructure\SystemMessage\Template;

use App\Domain\Infrastructure\SystemMessage\Contract\AbstractMessageTemplate;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;

/**
 * 警报通知模板
 */
class AlertNotification extends AbstractMessageTemplate
{
    public function __construct(
        protected string $title,
        protected string $content,
        protected array $userIds = []
    ) {}

    public function getTitle(): string
    {
        return '⚠️ ' . $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getType(): MessageType
    {
        return MessageType::ALERT;
    }

    public function getChannels(): array
    {
        return [MessageChannel::DATABASE, MessageChannel::EMAIL];
    }

    public function getPriority(): int
    {
        return 5;
    }

    protected function recipients(): array
    {
        return $this->userIds;
    }
}
