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

namespace Plugin\WecomScrm\Job;

use Hyperf\AsyncQueue\Job;
use Hyperf\Context\ApplicationContext;
use Plugin\WecomScrm\Library\Handler\GroupChatHandler;
use Plugin\WecomScrm\Model\WecomGroupChat;

final class SyncWecomGroupChatsJob extends Job
{
    public function handle(): void
    {
        $handler = ApplicationContext::getContainer()->get(GroupChatHandler::class);
        foreach ($handler->list([])['chat_id_list'] ?? [] as $chatId) {
            $chatId = (string) $chatId;
            if ($chatId === '') {
                continue;
            }
            $detail = $handler->get($chatId);
            WecomGroupChat::query()->updateOrCreate(
                ['chat_id' => $chatId],
                [
                    'name' => $detail['name'] ?? null,
                    'owner' => $detail['owner'] ?? null,
                    'member_count' => \count($detail['member_list'] ?? []),
                    'status' => 0,
                    'raw_data' => $detail,
                ],
            );
        }
    }
}
