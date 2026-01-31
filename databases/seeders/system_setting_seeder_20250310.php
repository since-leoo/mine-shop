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

use App\Domain\SystemSetting\Entity\SystemSettingEntity;
use App\Infrastructure\Model\Setting\SystemSetting;
use Hyperf\Database\Seeders\Seeder;

class SystemSettingSeeder20250310 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<string, mixed> $groups */
        $groups = config('mall.groups', []);

        foreach ($groups as $groupKey => $group) {
            $settings = $group['settings'] ?? [];
            foreach ($settings as $key => $setting) {
                $type = (string) ($setting['type'] ?? 'string');
                $label = (string) ($setting['label'] ?? $key);
                $description = $setting['description'] ?? null;
                $sort = (int) ($setting['sort'] ?? 0);
                $isSensitive = (bool) ($setting['is_sensitive'] ?? false);
                $meta = $setting['meta'] ?? [];
                $defaultValue = $setting['default'] ?? null;

                $entity = SystemSettingEntity::fromDefinition($groupKey, $key, $setting);
                $entity->setValue($defaultValue);

                SystemSetting::query()->updateOrCreate(
                    ['key' => $key],
                    $entity->toArray()
                );
            }
        }
    }
}
