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

use App\Infrastructure\Permission\Model\User;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id 主键
 * @property int $user_id 用户ID
 * @property array $channel_preferences 渠道偏好设置
 * @property array $type_preferences 消息类型偏好设置
 * @property bool $do_not_disturb_enabled 是否启用免打扰
 * @property string $do_not_disturb_start 免打扰开始时间
 * @property string $do_not_disturb_end 免打扰结束时间
 * @property int $min_priority 最小优先级
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 * @property User $user 用户
 */
class UserNotificationPreference extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_notification_preferences';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'user_id',
        'channel_preferences',
        'type_preferences',
        'do_not_disturb_enabled',
        'do_not_disturb_start',
        'do_not_disturb_end',
        'min_priority',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'channel_preferences' => 'json',
        'type_preferences' => 'json',
        'do_not_disturb_enabled' => 'boolean',
        'min_priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 默认渠道偏好.
     */
    public static function getDefaultChannelPreferences(): array
    {
        return [
            'socketio' => true,
            'websocket' => true,
            'email' => false,
            'sms' => false,
            'push' => false,
        ];
    }

    /**
     * 默认消息类型偏好.
     */
    public static function getDefaultTypePreferences(): array
    {
        return [
            'system' => true,
            'announcement' => true,
            'alert' => true,
            'reminder' => true,
            'marketing' => false,
        ];
    }

    /**
     * 用户关联.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取渠道设置.
     */
    public function getChannelSetting(string $channel): bool
    {
        $preferences = $this->channel_preferences ?? self::getDefaultChannelPreferences();
        return $preferences[$channel] ?? false;
    }

    /**
     * 获取消息类型设置.
     */
    public function getTypeSetting(string $type): bool
    {
        $preferences = $this->type_preferences ?? self::getDefaultTypePreferences();
        return $preferences[$type] ?? false;
    }

    /**
     * 检查当前时间是否在免打扰时间段内.
     */
    public function isInDoNotDisturbTime(): bool
    {
        if (! $this->do_not_disturb_enabled) {
            return false;
        }

        if (! $this->do_not_disturb_start || ! $this->do_not_disturb_end) {
            return false;
        }

        $now = Carbon::now();
        $start = Carbon::createFromTimeString($this->do_not_disturb_start);
        $end = Carbon::createFromTimeString($this->do_not_disturb_end);

        // 处理跨天的情况
        if ($start->greaterThan($end)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        return $now->between($start, $end);
    }

    /**
     * 检查是否应该发送通知.
     */
    public function shouldNotify(string $channel, string $type, int $priority = 1): bool
    {
        // 检查渠道是否启用
        if (! $this->getChannelSetting($channel)) {
            return false;
        }

        // 检查消息类型是否启用
        if (! $this->getTypeSetting($type)) {
            return false;
        }

        // 检查优先级
        if ($priority < ($this->min_priority ?? 1)) {
            return false;
        }

        // 检查免打扰时间
        if ($this->isInDoNotDisturbTime()) {
            return false;
        }

        return true;
    }

    /**
     * 作用域：按用户筛选.
     * @param mixed $query
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
