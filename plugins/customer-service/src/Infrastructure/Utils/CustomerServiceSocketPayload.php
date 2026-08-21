<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Utils;

final class CustomerServiceSocketPayload
{
    public static function payload(array $message): array
    {
        return is_array($message['payload'] ?? null) ? $message['payload'] : [];
    }

    public static function conversationNo(array $payload): string
    {
        return trim((string) ($payload['conversation_no'] ?? ''));
    }

    public static function clientMessageId(array $payload): string
    {
        return trim((string) ($payload['client_message_id'] ?? ''));
    }
}
