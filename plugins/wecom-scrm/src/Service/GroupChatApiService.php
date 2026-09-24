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

namespace Plugin\WecomScrm\Service;

use Plugin\WecomScrm\Library\Handler\GroupChatHandler;

final class GroupChatApiService
{
    public function __construct(private readonly GroupChatHandler $handler) {}

    public function list(array $filters = []): array
    {
        return $this->handler->list($filters);
    }

    public function detail(string $chatId): array
    {
        return $this->handler->get($chatId);
    }

    public function send(string $chatId, string $content): array
    {
        return $this->handler->sendMessage($chatId, $content);
    }
}
