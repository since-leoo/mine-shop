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

/**
 * @property int $id
 * @property string $conversation_no
 * @property int $member_id
 * @property string $status
 * @property string $reception_state
 * @property null|int $assigned_agent_id
 * @property int $generation
 * @property int $ai_turn
 * @property int $clarification_count
 * @property null|int $category_id
 * @property null|array $classification_json
 * @property null|int $ai_agent_id
 * @property int $config_revision
 * @property int $priority
 * @property string $source
 * @property null|string $subject
 * @property null|int $last_message_id
 * @property int $last_sequence
 * @property null|\Carbon\Carbon $last_message_at
 * @property null|string $queue_entered_at
 * @property null|string $assignment_expires_at
 * @property null|string $deferred_at
 * @property null|string $handoff_reason
 * @property string $resolution_status
 * @property null|string $resolution_source
 * @property null|string $resolved_at
 * @property null|int $closed_by
 * @property null|string $closed_reason
 * @property null|\Carbon\Carbon $closed_at
 * @property null|\Carbon\Carbon $created_at
 * @property null|\Carbon\Carbon $updated_at
 */
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
