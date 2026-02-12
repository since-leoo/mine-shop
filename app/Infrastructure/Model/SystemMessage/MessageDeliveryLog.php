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
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 主键
 * @property int $message_id 消息ID
 * @property int $user_id 用户ID
 * @property string $channel 传递渠道
 * @property string $status 传递状态
 * @property int $attempt_count 尝试次数
 * @property string $error_message 错误信息
 * @property array $metadata 元数据
 * @property Carbon $sent_at 发送时间
 * @property Carbon $delivered_at 送达时间
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property Message $message 消息
 * @property User $user 用户
 */
class MessageDeliveryLog extends Model
{
    public const CHANNEL_WEBSOCKET = 'websocket';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_MINIAPP = 'miniapp';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    protected ?string $table = 'message_delivery_logs';

    protected array $fillable = [
        'message_id',
        'user_id',
        'channel',
        'status',
        'attempt_count',
        'error_message',
        'metadata',
        'sent_at',
        'delivered_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'message_id' => 'integer',
        'user_id' => 'integer',
        'attempt_count' => 'integer',
        'metadata' => 'json',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => Carbon::now(),
        ]);
    }

    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => Carbon::now(),
        ]);
    }

    public function markAsFailed(?string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function incrementAttempt(): bool
    {
        return $this->increment('attempt_count');
    }

    public function isSuccessful(): bool
    {
        return \in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED], true);
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canRetry(): bool
    {
        $maxAttempts = config('system_message.notification.retry.max_attempts', 3);
        return $this->isFailed() && $this->attempt_count < $maxAttempts;
    }

    public function getDeliveryDuration(): ?int
    {
        if (! $this->sent_at || ! $this->delivered_at) {
            return null;
        }
        return $this->delivered_at->diffInSeconds($this->sent_at);
    }

    public function getProcessingDuration(): ?int
    {
        if (! $this->created_at || ! $this->sent_at) {
            return null;
        }
        return $this->sent_at->diffInSeconds($this->created_at);
    }

    public function setMetadata(string $key, $value): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        return $this->update(['metadata' => $metadata]);
    }

    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getStatusText(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => '待处理',
            self::STATUS_SENT => '已发送',
            self::STATUS_DELIVERED => '已送达',
            self::STATUS_FAILED => '失败',
            default => '未知',
        };
    }

    public function getChannelText(): string
    {
        return match ($this->channel) {
            self::CHANNEL_WEBSOCKET => 'WebSocket',
            self::CHANNEL_EMAIL => '邮件',
            self::CHANNEL_SMS => '短信',
            self::CHANNEL_MINIAPP => '小程序',
            default => '未知',
        };
    }

    public static function getChannels(): array
    {
        return [
            self::CHANNEL_WEBSOCKET => 'WebSocket',
            self::CHANNEL_EMAIL => '邮件',
            self::CHANNEL_SMS => '短信',
            self::CHANNEL_MINIAPP => '小程序',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_SENT => '已发送',
            self::STATUS_DELIVERED => '已送达',
            self::STATUS_FAILED => '失败',
        ];
    }

    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCanRetry($query)
    {
        $maxAttempts = config('system_message.notification.retry.max_attempts', 3);
        return $query->where('status', self::STATUS_FAILED)
            ->where('attempt_count', '<', $maxAttempts);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public static function getDeliveryStats(?int $messageId = null): array
    {
        $query = static::query();
        if ($messageId) {
            $query->where('message_id', $messageId);
        }
        $total = $query->count();
        $successful = $query->clone()->successful()->count();
        $failed = $query->clone()->failed()->count();
        $pending = $query->clone()->pending()->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }

    public static function getChannelStats(): array
    {
        return static::selectRaw('channel, status, COUNT(*) as count')
            ->groupBy(['channel', 'status'])
            ->get()
            ->groupBy('channel')
            ->map(static function ($logs) {
                $stats = ['total' => 0, 'successful' => 0, 'failed' => 0, 'pending' => 0];
                foreach ($logs as $log) {
                    $stats['total'] += $log->count;
                    if (\in_array($log->status, [self::STATUS_SENT, self::STATUS_DELIVERED], true)) {
                        $stats['successful'] += $log->count;
                    } elseif ($log->status === self::STATUS_FAILED) {
                        $stats['failed'] += $log->count;
                    } elseif ($log->status === self::STATUS_PENDING) {
                        $stats['pending'] += $log->count;
                    }
                }
                $stats['success_rate'] = $stats['total'] > 0
                    ? round(($stats['successful'] / $stats['total']) * 100, 2)
                    : 0;
                return $stats;
            })
            ->toArray();
    }
}
