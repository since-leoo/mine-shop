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

namespace App\Infrastructure\Model\SystemMessage;

use App\Infrastructure\Model\Permission\User;
use Carbon\Carbon;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageChannel;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageStatus;
use App\Domain\Infrastructure\SystemMessage\Enum\MessageType;
use App\Domain\Infrastructure\SystemMessage\Enum\RecipientType;

/**
 * @property int $id 消息ID，主键
 * @property string $title 消息标题
 * @property string $content 消息内容
 * @property string $type 消息类型
 * @property int $priority 优先级 1-5
 * @property int $sender_id 发送者ID
 * @property string $recipient_type 收件人类型
 * @property array $recipient_ids 收件人ID列表
 * @property int $template_id 模板ID
 * @property array $template_variables 模板变量
 * @property array $channels 传递渠道
 * @property Carbon $scheduled_at 计划发送时间
 * @property Carbon $sent_at 实际发送时间
 * @property string $status 状态
 * @property int $created_by 创建者
 * @property int $updated_by 更新者
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Carbon $deleted_at 删除时间
 * @property string $remark 备注
 * @property User $sender 发送者
 * @property MessageTemplate $template 消息模板
 * @property Collection|UserMessage[] $userMessages 用户消息关联
 * @property Collection|MessageDeliveryLog[] $deliveryLogs 传递日志
 */
class Message extends Model
{
    use SoftDeletes;

    public const RECIPIENT_ALL = 'all';

    protected ?string $table = 'system_messages';

    protected array $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'sender_id',
        'recipient_type',
        'recipient_ids',
        'template_id',
        'template_variables',
        'channels',
        'scheduled_at',
        'sent_at',
        'status',
        'created_by',
        'updated_by',
        'remark',
    ];

    protected array $casts = [
        'id' => 'integer',
        'priority' => 'integer',
        'sender_id' => 'integer',
        'template_id' => 'integer',
        'recipient_ids' => 'json',
        'template_variables' => 'json',
        'channels' => 'json',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id', 'id');
    }

    public function userMessages(): HasMany
    {
        return $this->hasMany(UserMessage::class, 'message_id', 'id');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(MessageDeliveryLog::class, 'message_id', 'id');
    }

    public function getRecipients(): \Hyperf\Collection\Collection
    {
        switch ($this->recipient_type) {
            case RecipientType::ALL->value:
                return User::where('status', 1)->select(['id', 'username', 'email', 'phone'])->get();
            case RecipientType::ROLE->value:
                if (empty($this->recipient_ids)) {
                    return collect();
                }
                return User::whereHas('roles', function ($query) {
                    $query->whereIn('id', $this->recipient_ids);
                })->where('status', 1)->select(['id', 'username', 'email', 'phone'])->get();
            case RecipientType::USER->value:
                if (empty($this->recipient_ids)) {
                    return collect();
                }
                return User::whereIn('id', $this->recipient_ids)
                    ->where('status', 1)
                    ->select(['id', 'username', 'email', 'phone'])
                    ->get();
            default:
                return collect();
        }
    }

    public function canSend(): bool
    {
        return \in_array($this->status, [
            MessageStatus::DRAFT->value,
            MessageStatus::SCHEDULED->value,
            MessageStatus::FAILED->value,
        ], true);
    }

    public function isSent(): bool
    {
        return $this->status === MessageStatus::SENT->value;
    }

    public function isSending(): bool
    {
        return $this->status === MessageStatus::SENDING->value;
    }

    public function isScheduled(): bool
    {
        return $this->status === MessageStatus::SCHEDULED->value && $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function shouldSend(): bool
    {
        if ($this->status !== MessageStatus::SCHEDULED->value) {
            return false;
        }
        if (! $this->scheduled_at) {
            return true;
        }
        return $this->scheduled_at->isPast();
    }

    public function getReadCount(): int
    {
        return $this->userMessages()->where('is_read', true)->count();
    }

    public function getUnreadCount(): int
    {
        return $this->userMessages()->where('is_read', false)->count();
    }

    public function getTotalRecipientCount(): int
    {
        return $this->userMessages()->count();
    }

    public function getDeliverySuccessRate(): float
    {
        $total = $this->deliveryLogs()->count();
        if ($total === 0) {
            return 0.0;
        }
        $success = $this->deliveryLogs()->whereIn('status', ['sent', 'delivered'])->count();
        return round(($success / $total) * 100, 2);
    }

    public function markAsSending(): bool
    {
        return $this->update(['status' => MessageStatus::SENDING->value]);
    }

    public function markAsSent(): bool
    {
        return $this->update([
            'status' => MessageStatus::SENT->value,
            'sent_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed(): bool
    {
        return $this->update(['status' => MessageStatus::FAILED->value]);
    }

    public static function getTypes(): array
    {
        return MessageType::toArray();
    }

    public static function getRecipientTypes(): array
    {
        return RecipientType::toArray();
    }

    public static function getStatuses(): array
    {
        return MessageStatus::toArray();
    }

    public static function getChannels(): array
    {
        return MessageChannel::toArray();
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySender($query, int $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    public function scopePendingSend($query)
    {
        return $query->where('status', MessageStatus::SCHEDULED->value)
            ->where(static function ($q) {
                $q->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', Carbon::now());
            });
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }
}
