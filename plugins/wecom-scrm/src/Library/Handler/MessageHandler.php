<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Library\Handler;

use Plugin\WecomScrm\Library\Abstract\WecomAbstract;
use Plugin\WecomScrm\Library\Interfaces\MessageInterface;

final class MessageHandler extends WecomAbstract implements MessageInterface
{
    public function sendText(array $touser, string $content, array $options = []): array
    {
        return $this->send('text', $touser, ['content' => $content] + $options);
    }

    public function sendMarkdown(array $touser, string $content, array $options = []): array
    {
        return $this->send('markdown', $touser, ['content' => $content] + $options);
    }

    protected function send(string $messageType, array $touser, array $payload): array
    {
        return $this->request('message/send', 'POST', [
            'touser' => implode('|', $touser),
            'msgtype' => $messageType,
            'agentid' => (int) make(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class)->get('mall.wecom.agent_id', 0),
            $messageType => $payload,
        ]);
    }

    public function result(array $data): array
    {
        return $data;
    }
}
