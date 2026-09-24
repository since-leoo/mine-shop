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

use Plugin\WecomScrm\Library\Handler\MessageHandler;

final class MessageApiService
{
    public function __construct(private readonly MessageHandler $handler) {}

    public function text(array $users, string $content): array
    {
        return $this->handler->sendText($users, $content);
    }

    public function markdown(array $users, string $content): array
    {
        return $this->handler->sendMarkdown($users, $content);
    }
}
