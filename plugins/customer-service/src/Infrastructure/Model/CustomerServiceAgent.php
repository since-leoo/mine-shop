<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceAgent extends Model
{
    protected ?string $table = 'customer_service_agents';

    protected array $fillable = ['admin_user_id', 'display_name', 'avatar', 'status', 'max_conversations', 'current_conversations', 'last_heartbeat_at'];

    protected array $casts = ['admin_user_id' => 'integer', 'max_conversations' => 'integer', 'current_conversations' => 'integer', 'last_heartbeat_at' => 'datetime'];
}
