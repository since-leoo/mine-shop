<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Library\Handler;

use Plugin\WecomScrm\Library\Abstract\WecomAbstract;
use Plugin\WecomScrm\Library\Interfaces\GroupChatInterface;

final class GroupChatHandler extends WecomAbstract implements GroupChatInterface
{
    public function list(array $statusFilter = []): array
    {
        return $this->request('externalcontact/groupchat/list', 'POST', $statusFilter);
    }

    public function get(string $chatId, bool $needMembers = true): array
    {
        return $this->request('externalcontact/groupchat/get', 'POST', [
            'chat_id' => $chatId,
            'need_name' => $needMembers ? 1 : 0,
        ]);
    }

    public function sendMessage(string $chatId, string $content): array
    {
        return $this->request('externalcontact/groupchat/send', 'POST', [
            'chat_id' => $chatId,
            'msgtype' => 'text',
            'text' => ['content' => $content],
        ]);
    }

    public function result(array $data): array
    {
        return $data;
    }
}
