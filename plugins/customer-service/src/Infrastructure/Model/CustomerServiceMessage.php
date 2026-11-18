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
 * @property string $sender_type
 * @property null|int $sender_id
 * @property string $client_message_id
 * @property int $sequence
 * @property string $message_type
 * @property array $content_json
 * @property null|int $reply_to_message_id
 * @property null|int $ai_run_id
 * @property string $visibility
 * @property null|array $evidence_json
 * @property null|\Carbon\Carbon $sent_at
 * @property null|\Carbon\Carbon $recalled_at
 * @property null|\Carbon\Carbon $created_at
 * @property null|\Carbon\Carbon $updated_at
 */
final class CustomerServiceMessage extends Model
{
    protected ?string $table = 'customer_service_messages';

    protected array $fillable = [
        'conversation_id', 'sender_type', 'sender_id', 'client_message_id', 'message_type',
        'content_json', 'reply_to_message_id', 'sent_at', 'recalled_at', 'sequence', 'ai_run_id', 'visibility', 'evidence_json',
    ];

    protected array $casts = [
        'sequence' => 'integer', 'ai_run_id' => 'integer', 'evidence_json' => 'array', 'conversation_id' => 'integer', 'sender_id' => 'integer', 'reply_to_message_id' => 'integer',
        'content_json' => 'array', 'sent_at' => 'datetime', 'recalled_at' => 'datetime',
    ];
}
