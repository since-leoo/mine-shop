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
 * @property string $message_type 消息类型
 * @property array $channels 启用的传递渠道
 * @property bool $is_enabled 是否启用
 * @property string $do_not_disturb_start 免打扰开始时间
 * @property string $do_not_disturb_end 免打扰结束时间
 * @property array $custom_settings 自定义设置
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
        'message_type',
        'channels',
        'is_enabled',
        'do_not_disturb_start',
        'do_not_disturb_end',
        'custom_settings',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'channels' => 'json',
        'is_enabled' => 'boolean',
        'custom_settings' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 消息类型常量
     */
    public const TYPE_SYSTEM = 'system';
    public const TYPE_ANNOUNCEMENT = 'announcement';
    public const TYPE_ALERT = 'alert';
    public const TYPE_REMINDER = 'reminder';

    /**
     * 传递渠道常量
     */
    public const CHANNEL_WEBSOCKET = 'websocket';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_MINIAPP = 'miniapp';

    /**
     * 用户关联
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 检查指定渠道是否启用
     */
    public function isChannelEnabled(string $channel): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        return in_array($channel, $this->channels ?? []);
    }

    /**
     * 启用指定渠道
     */
    public function enableChannel(string $channel): bool
    {
        $channels = $this->channels ?? [];
        
        if (!in_array($channel, $channels)) {
            $channels[] = $channel;
            return $this->update(['channels' => $channels]);
        }
        
        return true;
    }

    /**
     * 禁用指定渠道
     */
    public function disableChannel(string $channel): bool
    {
        $channels = $this->channels ?? [];
        $index = array_search($channel, $channels);
        
        if ($index !== false) {
            unset($channels[$index]);
            return $this->update(['channels' => array_values($channels)]);
        }
        
        return true;
    }

    /**
     * 设置免打扰时间
     */
    public function setDoNotDisturbTime(string $start, string $end): bool
    {
        return $this->update([
            'do_not_disturb_start' => $start,
            'do_not_disturb_end' => $end,
        ]);
    }

    /**
     * 清除免打扰时间
     */
    public function clearDoNotDisturbTime(): bool
    {
        return $this->update([
            'do_not_disturb_start' => null,
            'do_not_disturb_end' => null,
        ]);
    }

    /**
     * 检查当前时间是否在免打扰时间段内
     */
    public function isInDoNotDisturbTime(): bool
    {
        if (!$this->do_not_disturb_start || !$this->do_not_disturb_end) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->do_not_disturb_start;
        $end = $this->do_not_disturb_end;

        // 处理跨天的情况
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * 检查是否应该发送通知
     */
    public function shouldNotify(string $channel): bool
    {
        // 检查偏好是否启用
        if (!$this->is_enabled) {
            return false;
        }

        // 检查渠道是否启用
        if (!$this->isChannelEnabled($channel)) {
            return false;
        }

        // 检查免打扰时间
        if ($this->isInDoNotDisturbTime()) {
            return false;
        }

        return true;
    }

    /**
     * 获取自定义设置值
     */
    public function getCustomSetting(string $key, $default = null)
    {
        return $this->custom_settings[$key] ?? $default;
    }

    /**
     * 设置自定义设置值
     */
    public function setCustomSetting(string $key, $value): bool
    {
        $settings = $this->custom_settings ?? [];
        $settings[$key] = $value;
        
        return $this->update(['custom_settings' => $settings]);
    }

    /**
     * 获取所有消息类型
     */
    public static function getMessageTypes(): array
    {
        return [
            self::TYPE_SYSTEM => '系统消息',
            self::TYPE_ANNOUNCEMENT => '公告',
            self::TYPE_ALERT => '警报',
            self::TYPE_REMINDER => '提醒',
        ];
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
     * 获取默认偏好设置
     */
    public static function getDefaultPreferences(): array
    {
        return config('system_message.preferences.defaults', [
            self::TYPE_SYSTEM => [self::CHANNEL_WEBSOCKET],
            self::TYPE_ANNOUNCEMENT => [self::CHANNEL_WEBSOCKET, self::CHANNEL_EMAIL],
            self::TYPE_ALERT => [self::CHANNEL_WEBSOCKET, self::CHANNEL_EMAIL],
            self::TYPE_REMINDER => [self::CHANNEL_WEBSOCKET],
        ]);
    }

    /**
     * 为用户创建默认偏好设置
     */
    public static function createDefaultForUser(int $userId): void
    {
        $defaults = self::getDefaultPreferences();
        
        foreach ($defaults as $messageType => $channels) {
            self::updateOrCreate(
                [
                    'user_id' => $userId,
                    'message_type' => $messageType,
                ],
                [
                    'channels' => $channels,
                    'is_enabled' => true,
                ]
            );
        }
    }

    /**
     * 获取用户的所有偏好设置
     */
    public static function getUserPreferences(int $userId): array
    {
        $preferences = self::where('user_id', $userId)->get()->keyBy('message_type');
        $defaults = self::getDefaultPreferences();
        $result = [];
        
        foreach ($defaults as $messageType => $defaultChannels) {
            if (isset($preferences[$messageType])) {
                $result[$messageType] = $preferences[$messageType];
            } else {
                // 如果没有设置，创建默认设置
                $result[$messageType] = self::create([
                    'user_id' => $userId,
                    'message_type' => $messageType,
                    'channels' => $defaultChannels,
                    'is_enabled' => true,
                ]);
            }
        }
        
        return $result;
    }

    /**
     * 批量更新用户偏好设置
     */
    public static function updateUserPreferences(int $userId, array $preferences): bool
    {
        foreach ($preferences as $messageType => $settings) {
            self::updateOrCreate(
                [
                    'user_id' => $userId,
                    'message_type' => $messageType,
                ],
                $settings
            );
        }
        
        return true;
    }

    /**
     * 作用域：按用户筛选
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 作用域：按消息类型筛选
     */
    public function scopeForMessageType($query, string $messageType)
    {
        return $query->where('message_type', $messageType);
    }

    /**
     * 作用域：启用的偏好
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * 作用域：禁用的偏好
     */
    public function scopeDisabled($query)
    {
        return $query->where('is_enabled', false);
    }

    /**
     * 作用域：支持指定渠道的偏好
     */
    public function scopeWithChannel($query, string $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }
}