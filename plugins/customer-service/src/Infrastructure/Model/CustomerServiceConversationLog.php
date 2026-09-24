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

final class CustomerServiceConversationLog extends Model
{
    protected ?string $table = 'customer_service_conversation_logs';

    protected array $fillable = ['conversation_id', 'action', 'operator_type', 'operator_id', 'detail_json'];

    protected array $casts = ['conversation_id' => 'integer', 'operator_id' => 'integer', 'detail_json' => 'array'];
}
