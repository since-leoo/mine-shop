<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceMessage extends Model
{
    protected ?string $table = 'customer_service_messages';

    protected array $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'client_message_id', 'message_type',
        'content_json', 'reply_to_message_id', 'sent_at', 'recalled_at',
    ];

    protected array $casts = [
        'conversation_id' => 'integer', 'sender_id' => 'integer', 'reply_to_message_id' => 'integer',
        'content_json' => 'array', 'sent_at' => 'datetime', 'recalled_at' => 'datetime',
    ];
}
