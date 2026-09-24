<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Library\Interfaces;

interface GroupChatInterface
{
    public function list(array $statusFilter = []): array;
    public function get(string $chatId, bool $needMembers = true): array;
    public function sendMessage(string $chatId, string $content): array;
}
