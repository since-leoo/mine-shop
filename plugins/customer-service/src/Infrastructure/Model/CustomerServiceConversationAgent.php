<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceConversationAgent extends Model
{
    protected ?string $table = 'customer_service_conversation_agents';

    protected array $fillable = ['conversation_id', 'agent_id', 'action', 'operator_admin_user_id', 'reason'];

    protected array $casts = ['conversation_id' => 'integer', 'agent_id' => 'integer', 'operator_admin_user_id' => 'integer'];
}
