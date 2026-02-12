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
 * @property array $channel_preferences 渠道偏好设置
 * @property array $type_preferences 消息类型偏好设置
 * @property bool $do_not_disturb_enabled 是否启用免打扰
 * @property string $do_not_disturb_start 免打扰开始时间
 * @property string $do_not_disturb_end 免打扰结束时间
 * @property int $min_priority 最小优先级
 * @property Carbon $created_at 创建时间
 * @property Carbon $updated_at 更新时间
 */
class UserNotificationPreference extends Model
{
    protected ?string $table = 'user_notification_preferences';

    protected array $fillable = [
        'user_id', 'channel_preferences', 'type_preferences',
        'do_not_disturb_enabled', 'do_not_disturb_start', 'do_not_disturb_end', 'min_priority',
    ];

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

    public static function getDefaultChannelPreferences(): array
    {
        return [
            'database' => true, 'socketio' => true, 'websocket' => false,
            'email' => false, 'sms' => false, 'push' => false,
        ];
    }

    public static function getDefaultTypePreferences(): array
    {
        return [
            'system' => true, 'announcement' => true, 'alert' => true,
            'reminder' => true, 'marketing' => false,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getChannelSetting(string $channel): bool
    {
        $preferences = $this->channel_preferences ?? self::getDefaultChannelPreferences();
        return $preferences[$channel] ?? false;
    }

    public function getTypeSetting(string $type): bool
    {
        $preferences = $this->type_preferences ?? self::getDefaultTypePreferences();
        return $preferences[$type] ?? false;
    }

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
        if ($start->greaterThan($end)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }
        return $now->between($start, $end);
    }

    public function shouldNotify(string $channel, string $type, int $priority = 1): bool
    {
        if (! $this->getChannelSetting($channel)) {
            return false;
        }
        if (! $this->getTypeSetting($type)) {
            return false;
        }
        if ($priority < ($this->min_priority ?? 1)) {
            return false;
        }
        if ($this->isInDoNotDisturbTime()) {
            return false;
        }
        return true;
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
