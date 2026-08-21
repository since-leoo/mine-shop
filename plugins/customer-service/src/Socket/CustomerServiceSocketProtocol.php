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

namespace Plugin\CustomerService\Socket;

final class CustomerServiceSocketProtocol
{
    public function decode(string $frame): ?array
    {
        try {
            $payload = json_decode($frame, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        return \is_array($payload) && \is_string($payload['event'] ?? null) ? $payload : null;
    }

    public function encode(string $event, array $payload = [], array $context = []): string
    {
        return json_encode([
            'event_id' => $context['event_id'] ?? bin2hex(random_bytes(8)),
            'event' => $event,
            'occurred_at' => date(\DATE_ATOM),
            'request_id' => $context['request_id'] ?? null,
            'conversation_no' => $context['conversation_no'] ?? null,
            'client_message_id' => $context['client_message_id'] ?? null,
            'payload' => $payload,
        ], \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }
}
