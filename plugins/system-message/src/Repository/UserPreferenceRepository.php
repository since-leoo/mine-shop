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

use Plugin\Since\SystemMessage\Model\UserNotificationPreference;

class UserPreferenceRepository
{
    /**
     * 获取用户偏好设置.
     */
    public function getUserPreference(int $userId): ?UserNotificationPreference
    {
        return UserNotificationPreference::where('user_id', $userId)->first();
    }

    /**
     * 创建或更新用户偏好设置.
     */
    public function createOrUpdate(int $userId, array $data): UserNotificationPreference
    {
        return UserNotificationPreference::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    /**
     * 更新用户渠道偏好.
     */
    public function updateChannelPreferences(int $userId, array $channels): bool
    {
        $preference = $this->getUserPreference($userId);

        if (! $preference) {
            $preference = new UserNotificationPreference(['user_id' => $userId]);
        }

        $preference->channel_preferences = $channels;
        return $preference->save();
    }

    /**
     * 更新用户消息类型偏好.
     */
    public function updateTypePreferences(int $userId, array $types): bool
    {
        $preference = $this->getUserPreference($userId);

        if (! $preference) {
            $preference = new UserNotificationPreference(['user_id' => $userId]);
        }

        $preference->type_preferences = $types;
        return $preference->save();
    }

    /**
     * 设置免打扰时间.
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
     * 启用/禁用免打扰.
     */
    public function toggleDoNotDisturb(int $userId, bool $enabled): bool
    {
        $preference = $this->getUserPreference($userId);

        if (! $preference) {
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
     * 设置最小优先级.
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
     * 获取默认渠道偏好.
     */
    protected function getDefaultChannelPreferences(): array
    {
        return config('system_message.notification.default_channels', [
            'database' => true,
            'email' => false,
            'sms' => false,
            'push' => false,
        ]);
    }

    /**
     * 获取默认消息类型偏好.
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
