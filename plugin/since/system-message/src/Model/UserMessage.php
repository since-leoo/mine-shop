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
 * @property int $user_id 用户ID
 * @property int $message_id 消息ID
 * @property bool $is_read 是否已读
 * @property Carbon $read_at 阅读时间
 * @property bool $is_deleted 是否删除
 * @property Carbon $deleted_at 删除时间
 * @property Carbon $created_at 创建时间
 * @property User $user 用户
 * @property Message $message 消息
 */
class UserMessage extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_messages';

    /**
     * Indicates if the model should be timestamped.
     */
    public bool $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'user_id',
        'message_id',
        'is_read',
        'read_at',
        'is_deleted',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
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

    /**
     * 用户关联
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 消息关联
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    /**
     * 标记为已读
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * 标记为未读
     */
    public function markAsUnread(): bool
    {
        if (!$this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * 软删除消息
     */
    public function softDelete(): bool
    {
        if ($this->is_deleted) {
            return true;
        }

        return $this->update([
            'is_deleted' => true,
            'deleted_at' => now(),
        ]);
    }

    /**
     * 恢复删除的消息
     */
    public function restore(): bool
    {
        if (!$this->is_deleted) {
            return true;
        }

        return $this->update([
            'is_deleted' => false,
            'deleted_at' => null,
        ]);
    }

    /**
     * 检查消息是否已读
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * 检查消息是否未读
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * 检查消息是否已删除
     */
    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }

    /**
     * 获取阅读状态文本
     */
    public function getReadStatusText(): string
    {
        return $this->is_read ? '已读' : '未读';
    }

    /**
     * 作用域：按用户筛选
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按消息筛选
     */
    public function scopeForMessage($query, int $messageId)
    {
        return $query->where('message_id', $messageId);
    }

    /**
     * 作用域：已读消息
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * 作用域：未读消息
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * 作用域：未删除的消息
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    /**
     * 作用域：已删除的消息
     */
    public function scopeDeleted($query)
    {
        return $query->where('is_deleted', true);
    }

    /**
     * 作用域：最近的消息
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 批量标记为已读
     */
    public static function batchMarkAsRead(int $userId, array $messageIds): int
    {
        return static::where('user_id', $userId)
            ->whereIn('message_id', $messageIds)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * 批量软删除
     */
    public static function batchSoftDelete(int $userId, array $messageIds): int
    {
        return static::where('user_id', $userId)
            ->whereIn('message_id', $messageIds)
            ->where('is_deleted', false)
            ->update([
                'is_deleted' => true,
                'deleted_at' => now(),
            ]);
    }

    /**
     * 获取用户的未读消息数量
     */
    public static function getUnreadCount(int $userId): int
    {
        return static::where('user_id', $userId)
            ->where('is_read', false)
            ->where('is_deleted', false)
            ->count();
    }

    /**
     * 获取用户的消息统计
     */
    public static function getUserMessageStats(int $userId): array
    {
        $total = static::where('user_id', $userId)
            ->where('is_deleted', false)
            ->count();

        $unread = static::where('user_id', $userId)
            ->where('is_read', false)
            ->where('is_deleted', false)
            ->count();

        $read = $total - $unread;

        return [
            'total' => $total,
            'read' => $read,
            'unread' => $unread,
        ];
    }
}