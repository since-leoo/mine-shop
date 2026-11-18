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
 * @property int $admin_user_id
 * @property string $display_name
 * @property null|string $avatar
 * @property string $status
 * @property bool $enabled
 * @property int $priority
 * @property int $weight
 * @property int $max_conversations
 * @property int $current_conversations
 * @property null|array $schedule_json
 * @property null|\Carbon\Carbon $last_heartbeat_at
 * @property null|string $last_assigned_at
 * @property null|\Carbon\Carbon $created_at
 * @property null|\Carbon\Carbon $updated_at
 */
final class CustomerServiceAgent extends Model
{
    protected ?string $table = 'customer_service_agents';

    protected array $fillable = ['admin_user_id', 'display_name', 'avatar', 'status', 'max_conversations', 'current_conversations', 'last_heartbeat_at', 'enabled', 'priority', 'weight', 'schedule_json', 'last_assigned_at'];

    protected array $casts = ['enabled' => 'boolean', 'priority' => 'integer', 'weight' => 'integer', 'schedule_json' => 'array', 'admin_user_id' => 'integer', 'max_conversations' => 'integer', 'current_conversations' => 'integer', 'last_heartbeat_at' => 'datetime'];
}
