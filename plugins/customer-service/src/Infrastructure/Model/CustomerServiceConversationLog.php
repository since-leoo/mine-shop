<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Model;

use Hyperf\DbConnection\Model\Model;

final class CustomerServiceConversationLog extends Model
{
    protected ?string $table = 'customer_service_conversation_logs';

    protected array $fillable = ['conversation_id', 'action', 'operator_type', 'operator_id', 'detail_json'];

    protected array $casts = ['conversation_id' => 'integer', 'operator_id' => 'integer', 'detail_json' => 'array'];
}
