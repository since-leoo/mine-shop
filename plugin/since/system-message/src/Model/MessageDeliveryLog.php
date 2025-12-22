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

namespace Plugin\Since\SystemMessage\Model;

use App\Model\Permission\User;
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
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'message_delivery_logs';

    /**
     * The attributes that are mass assignable.
     */
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

    /**
     * The attributes that should be cast to native types.
     */
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

    /**
     * 传递渠道常量
     */
    public const CHANNEL_WEBSOCKET = 'websocket';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_MINIAPP = 'miniapp';

    /**
     * 传递状态常量
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    /**
     * 消息关联
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    /**
     * 用户关联
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 标记为已发送
     */
    public function markAsSent(): bool
    {
        return $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * 标记为已送达
     */
    public function markAsDelivered(): bool
    {
        return $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * 标记为失败
     */
    public function markAsFailed(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * 增加尝试次数
     */
    public function incrementAttempt(): bool
    {
        return $this->increment('attempt_count');
    }

    /**
     * 检查是否成功
     */
    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * 检查是否失败
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * 检查是否待处理
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * 检查是否可以重试
     */
    public function canRetry(): bool
    {
        $maxAttempts = config('system_message.notification.retry.max_attempts', 3);
        return $this->isFailed() && $this->attempt_count < $maxAttempts;
    }

    /**
     * 获取传递耗时（秒）
     */
    public function getDeliveryDuration(): ?int
    {
        if (!$this->sent_at || !$this->delivered_at) {
            return null;
        }

        return $this->delivered_at->diffInSeconds($this->sent_at);
    }

    /**
     * 获取处理耗时（秒）
     */
    public function getProcessingDuration(): ?int
    {
        if (!$this->created_at || !$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffInSeconds($this->created_at);
    }

    /**
     * 设置元数据
     */
    public function setMetadata(string $key, $value): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        
        return $this->update(['metadata' => $metadata]);
    }

    /**
     * 获取元数据
     */
    public function getMetadata(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * 获取状态文本
     */
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

    /**
     * 获取渠道文本
     */
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

    /**
     * 获取所有传递渠道
     */
    public static function getChannels(): array
    {
        return [
            self::CHANNEL_WEBSOCKET => 'WebSocket',
            self::CHANNEL_EMAIL => '邮件',
            self::CHANNEL_SMS => '短信',
            self::CHANNEL_MINIAPP => '小程序',
        ];
    }

    /**
     * 获取所有传递状态
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_SENT => '已发送',
            self::STATUS_DELIVERED => '已送达',
            self::STATUS_FAILED => '失败',
        ];
    }

    /**
     * 作用域：按消息筛选
     */
    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * 作用域：按用户筛选
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按渠道筛选
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * 作用域：按状态筛选
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 作用域：成功的传递
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * 作用域：失败的传递
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * 作用域：待处理的传递
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * 作用域：可重试的传递
     */
    public function scopeCanRetry($query)
    {
        $maxAttempts = config('system_message.notification.retry.max_attempts', 3);
        return $query->where('status', self::STATUS_FAILED)
            ->where('attempt_count', '<', $maxAttempts);
    }

    /**
     * 作用域：最近的日志
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 获取传递统计
     */
    public static function getDeliveryStats(int $messageId = null): array
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

    /**
     * 获取渠道统计
     */
    public static function getChannelStats(): array
    {
        return static::selectRaw('channel, status, COUNT(*) as count')
            ->groupBy(['channel', 'status'])
            ->get()
            ->groupBy('channel')
            ->map(function ($logs) {
                $stats = ['total' => 0, 'successful' => 0, 'failed' => 0, 'pending' => 0];
                
                foreach ($logs as $log) {
                    $stats['total'] += $log->count;
                    
                    if (in_array($log->status, [self::STATUS_SENT, self::STATUS_DELIVERED])) {
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