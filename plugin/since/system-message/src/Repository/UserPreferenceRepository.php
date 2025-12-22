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

namespace Plugin\Since\SystemMessage\Repository;

use Hyperf\Collection\Collection;
use Plugin\Since\SystemMessage\Model\UserNotificationPreference;

class UserPreferenceRepository
{
    /**
     * 获取用户偏好设置
     */
    public function getUserPreference(int $userId): ?UserNotificationPreference
    {
        return UserNotificationPreference::where('user_id', $userId)->first();
    }

    /**
     * 创建或更新用户偏好设置
     */
    public function createOrUpdate(int $userId, array $data): UserNotificationPreference
    {
        return UserNotificationPreference::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    /**
     * 获取用户渠道偏好
     */
    public function getUserChannelPreferences(int $userId): array
    {
        $preference = $this->getUserPreference($userId);
        
        if (!$preference) {
            return $this->getDefaultChannelPreferences();
        }

        return $preference->channel_preferences ?? $this->getDefaultChannelPreferences();
    }

    /**
     * 更新用户渠道偏好
     */
    public function updateChannelPreferences(int $userId, array $channels): bool
    {
        $preference = $this->getUserPreference($userId);
        
        if (!$preference) {
            $preference = new UserNotificationPreference(['user_id' => $userId]);
        }

        $preference->channel_preferences = $channels;
        return $preference->save();
    }

    /**
     * 获取用户消息类型偏好
     */
    public function getUserTypePreferences(int $userId): array
    {
        $preference = $this->getUserPreference($userId);
        
        if (!$preference) {
            return $this->getDefaultTypePreferences();
        }

        return $preference->type_preferences ?? $this->getDefaultTypePreferences();
    }

    /**
     * 更新用户消息类型偏好
     */
    public function updateTypePreferences(int $userId, array $types): bool
    {
        $preference = $this->getUserPreference($userId);
        
        if (!$preference) {
            $preference = new UserNotificationPreference(['user_id' => $userId]);
        }

        $preference->type_preferences = $types;
        return $preference->save();
    }

    /**
     * 设置免打扰时间
     */
    public function setDoNotDisturbTime(int $userId, string $startTime, string $endTime, bool $enabled = true): bool
    {
        return $this->createOrUpdate($userId, [
            'do_not_disturb_enabled' => $enabled,
            'do_not_disturb_start' => $startTime,
            'do_not_disturb_end' => $endTime,
        ])->exists;
    }

    /**
     * 启用/禁用免打扰
     */
    public function toggleDoNotDisturb(int $userId, bool $enabled): bool
    {
        $preference = $this->getUserPreference($userId);
        
        if (!$preference) {
            $preference = new UserNotificationPreference([
                'user_id' => $userId,
                'do_not_disturb_enabled' => $enabled,
            ]);
        } else {
            $preference->do_not_disturb_enabled = $enabled;
        }

        return $preference->save();
    }

    /**
     * 设置最小优先级
     */
    public function setMinPriority(int $userId, int $priority): bool
    {
        return $this->createOrUpdate($userId, [
            'min_priority' => $priority,
        ])->exists;
    }

    /**
     * 重置用户偏好为默认值
     */
    public function resetToDefault(int $userId): bool
    {
        return $this->createOrUpdate($userId, [
            'channel_preferences' => $this->getDefaultChannelPreferences(),
            'type_preferences' => $this->getDefaultTypePreferences(),
            'do_not_disturb_enabled' => false,
            'do_not_disturb_start' => '22:00:00',
            'do_not_disturb_end' => '08:00:00',
            'min_priority' => 1,
        ])->exists;
    }

    /**
     * 批量获取用户偏好
     */
    public function getBatchUserPreferences(array $userIds): Collection
    {
        return UserNotificationPreference::whereIn('user_id', $userIds)->get();
    }

    /**
     * 获取启用了特定渠道的用户
     */
    public function getUsersWithChannelEnabled(string $channel): Collection
    {
        return UserNotificationPreference::whereJsonContains('channel_preferences->' . $channel, true)
            ->get();
    }

    /**
     * 获取启用了特定消息类型的用户
     */
    public function getUsersWithTypeEnabled(string $type): Collection
    {
        return UserNotificationPreference::whereJsonContains('type_preferences->' . $type, true)
            ->get();
    }

    /**
     * 获取偏好统计
     */
    public function getPreferenceStatistics(): array
    {
        $total = UserNotificationPreference::count();
        
        // 渠道偏好统计
        $channelStats = [];
        $channels = ['websocket', 'email', 'sms', 'push'];
        foreach ($channels as $channel) {
            $channelStats[$channel] = UserNotificationPreference::whereJsonContains('channel_preferences->' . $channel, true)->count();
        }

        // 免打扰统计
        $dndEnabled = UserNotificationPreference::where('do_not_disturb_enabled', true)->count();

        // 优先级统计
        $priorityStats = UserNotificationPreference::selectRaw('min_priority, COUNT(*) as count')
            ->groupBy('min_priority')
            ->pluck('count', 'min_priority')
            ->toArray();

        return [
            'total_users_with_preferences' => $total,
            'channel_preferences' => $channelStats,
            'do_not_disturb_enabled' => $dndEnabled,
            'priority_preferences' => $priorityStats,
        ];
    }

    /**
     * 获取默认渠道偏好
     */
    protected function getDefaultChannelPreferences(): array
    {
        return config('system_message.notification.default_channels', [
            'socketio' => true,
            'websocket' => true,
            'email' => false,
            'sms' => false,
            'push' => false,
        ]);
    }

    /**
     * 获取默认消息类型偏好
     */
    protected function getDefaultTypePreferences(): array
    {
        return config('system_message.notification.default_types', [
            'system' => true,
            'announcement' => true,
            'alert' => true,
            'reminder' => true,
            'marketing' => false,
        ]);
    }
}