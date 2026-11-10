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

namespace Plugin\SupplyChain\Handler\Yunzhong;

use Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong\YunzhongClient;
use Plugin\SupplyChain\Interfaces\MessageInterface;

final class MessageHandler implements MessageInterface
{
    public function __construct(private readonly YunzhongClient $client) {}

    public function pull(int $pageSize = 100): array
    {
        if ($pageSize < 1 || $pageSize > 100) {
            throw new \InvalidArgumentException('消息拉取数量必须为 1 至 100');
        }
        $data = $this->client->call('message.pull', 'POST', '/app/application/getMessage', ['page_size' => $pageSize]);
        return array_map(static function (array $message): array {
            $content = $message['content'] ?? null;
            $payload = \is_string($content) ? json_decode($content, true, 512, \JSON_THROW_ON_ERROR) : $content;
            if (! \is_array($payload) || empty($message['type_string'])) {
                throw new \RuntimeException('供应链消息类型或内容无效，禁止确认消息');
            }
            unset($payload['member_sign'], $payload['app_secret']);
            return ['external_message_id' => (string) PayloadMapper::id($message['id'] ?? ''),
                'type' => (string) $message['type_string'], 'payload' => $payload];
        }, PayloadMapper::list($data));
    }

    public function acknowledge(array $externalMessageIds): void
    {
        $this->client->call('message.acknowledge', 'POST', '/app/application/deleteMessage', ['ids' => PayloadMapper::ids($externalMessageIds, 100)], false);
    }
}
