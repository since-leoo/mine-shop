<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceConversation extends Model
{
    protected ?string $table = 'customer_service_conversations';

    protected array $fillable = [
        'conversation_no', 'member_id', 'status', 'assigned_agent_id', 'source', 'subject',
        'last_message_id', 'last_message_at', 'closed_by', 'closed_reason', 'closed_at',
    ];

    protected array $casts = [
        'member_id' => 'integer', 'assigned_agent_id' => 'integer', 'last_message_id' => 'integer',
        'closed_by' => 'integer', 'last_message_at' => 'datetime', 'closed_at' => 'datetime',
    ];
}
