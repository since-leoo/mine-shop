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
 * @property int $user_id 用户ID
 * @property int $message_id 消息ID
 * @property bool $is_read 是否已读
 * @property Carbon $read_at 阅读时间
 * @property bool $is_deleted 是否删除
 * @property Carbon $deleted_at 删除时间
 * @property Carbon $created_at 创建时间
 */
class UserMessage extends Model
{
    public bool $timestamps = false;

    protected ?string $table = 'user_messages';

    protected array $fillable = [
        'user_id', 'message_id', 'is_read', 'read_at', 'is_deleted', 'deleted_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'message_id' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'is_deleted' => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }
        return $this->update(['is_read' => true, 'read_at' => Carbon::now()]);
    }

    public function markAsUnread(): bool
    {
        if (! $this->is_read) {
            return true;
        }
        return $this->update(['is_read' => false, 'read_at' => null]);
    }

    public function softDelete(): bool
    {
        if ($this->is_deleted) {
            return true;
        }
        return $this->update(['is_deleted' => true, 'deleted_at' => Carbon::now()]);
    }

    public function restore(): bool
    {
        if (! $this->is_deleted) {
            return true;
        }
        return $this->update(['is_deleted' => false, 'deleted_at' => null]);
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function isUnread(): bool
    {
        return ! $this->is_read;
    }

    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }

    public function getReadStatusText(): string
    {
        return $this->is_read ? '已读' : '未读';
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeDeleted($query)
    {
        return $query->where('is_deleted', true);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public static function batchMarkAsRead(int $userId, array $messageIds): int
    {
        return static::where('user_id', $userId)
            ->whereIn('message_id', $messageIds)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);
    }

    public static function batchSoftDelete(int $userId, array $messageIds): int
    {
        return static::where('user_id', $userId)
            ->whereIn('message_id', $messageIds)
            ->where('is_deleted', false)
            ->update(['is_deleted' => true, 'deleted_at' => Carbon::now()]);
    }

    public static function getUnreadCount(int $userId): int
    {
        return static::where('user_id', $userId)
            ->where('is_read', false)
            ->where('is_deleted', false)
            ->count();
    }

    public static function getUserMessageStats(int $userId): array
    {
        $total = static::where('user_id', $userId)->where('is_deleted', false)->count();
        $unread = static::where('user_id', $userId)->where('is_read', false)->where('is_deleted', false)->count();
        $read = $total - $unread;
        return ['total' => $total, 'read' => $read, 'unread' => $unread];
    }
}
