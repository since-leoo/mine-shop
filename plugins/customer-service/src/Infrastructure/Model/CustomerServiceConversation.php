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

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceConversation extends Model
{
    protected ?string $table = 'customer_service_conversations';

    protected array $fillable = [
        'conversation_no', 'member_id', 'status', 'assigned_agent_id', 'source', 'subject',
        'last_message_id', 'last_message_at', 'closed_by', 'closed_reason', 'closed_at',
        'reception_state', 'generation', 'ai_turn', 'clarification_count', 'category_id', 'classification_json', 'ai_agent_id', 'config_revision', 'priority', 'last_sequence', 'queue_entered_at', 'assignment_expires_at', 'deferred_at', 'handoff_reason', 'resolution_status', 'resolution_source', 'resolved_at',
    ];

    protected array $casts = [
        'classification_json' => 'array', 'generation' => 'integer', 'ai_turn' => 'integer', 'last_sequence' => 'integer', 'category_id' => 'integer', 'ai_agent_id' => 'integer', 'config_revision' => 'integer', 'priority' => 'integer', 'clarification_count' => 'integer',
        'member_id' => 'integer', 'assigned_agent_id' => 'integer', 'last_message_id' => 'integer',
        'closed_by' => 'integer', 'last_message_at' => 'datetime', 'closed_at' => 'datetime',
    ];
}
