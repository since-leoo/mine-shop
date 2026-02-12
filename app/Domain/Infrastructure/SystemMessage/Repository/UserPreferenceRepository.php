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

namespace App\Domain\Infrastructure\SystemMessage\Repository;

use App\Infrastructure\Model\SystemMessage\UserNotificationPreference;

class UserPreferenceRepository
{
    public function getUserPreference(int $userId): ?UserNotificationPreference
    {
        return UserNotificationPreference::where('user_id', $userId)->first();
    }

    public function createOrUpdate(int $userId, array $data): UserNotificationPreference
    {
        return UserNotificationPreference::updateOrCreate(['user_id' => $userId], $data);
    }

    public function updateChannelPreferences(int $userId, array $channels): bool
    {
        $preference = $this->getUserPreference($userId);
        if (! $preference) {
            $preference = new UserNotificationPreference(['user_id' => $userId]);
        }
        $preference->channel_preferences = $channels;
        return $preference->save();
    }

    public function updateTypePreferences(int $userId, array $types): bool
    {
        $preference = $this->getUserPreference($userId);
        if (! $preference) {
            $preference = new UserNotificationPreference(['user_id' => $userId]);
        }
        $preference->type_preferences = $types;
        return $preference->save();
    }

    public function setDoNotDisturbTime(int $userId, string $startTime, string $endTime, bool $enabled = true): bool
    {
        return $this->createOrUpdate($userId, [
            'do_not_disturb_enabled' => $enabled,
            'do_not_disturb_start' => $startTime,
            'do_not_disturb_end' => $endTime,
        ])->exists;
    }

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

    public function setMinPriority(int $userId, int $priority): bool
    {
        return $this->createOrUpdate($userId, ['min_priority' => $priority])->exists;
    }

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

    protected function getDefaultChannelPreferences(): array
    {
        return config('system_message.notification.default_channels', [
            'database' => true, 'email' => false, 'sms' => false, 'push' => false,
        ]);
    }

    protected function getDefaultTypePreferences(): array
    {
        return config('system_message.notification.default_types', [
            'system' => true, 'announcement' => true, 'alert' => true,
            'reminder' => true, 'marketing' => false,
        ]);
    }
}
