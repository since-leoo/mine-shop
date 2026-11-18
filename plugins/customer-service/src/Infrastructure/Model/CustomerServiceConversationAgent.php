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
 * @property int $conversation_id
 * @property int $agent_id
 * @property string $action
 * @property null|int $operator_admin_user_id
 * @property null|string $reason
 * @property null|\Carbon\Carbon $created_at
 * @property null|\Carbon\Carbon $updated_at
 */
final class CustomerServiceConversationAgent extends Model
{
    protected ?string $table = 'customer_service_conversation_agents';

    protected array $fillable = ['conversation_id', 'agent_id', 'action', 'operator_admin_user_id', 'reason'];

    protected array $casts = ['conversation_id' => 'integer', 'agent_id' => 'integer', 'operator_admin_user_id' => 'integer'];
}
