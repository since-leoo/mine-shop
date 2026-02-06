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

namespace App\Application\Mapper;

use App\Domain\SystemSetting\Entity\SystemSettingEntity;

final class SystemSettingAssembler
{
    public function fromRequest(string $key, mixed $value): SystemSettingEntity
    {
        $entity = (new SystemSettingEntity())
            ->setKey($key)
            ->setType($this->resolveType($key, $value));

        return $entity->setValue($value);
    }

    private function resolveType(string $key, mixed $value): string
    {
        foreach (config('mall.groups', []) as $group) {
            $settings = $group['settings'] ?? [];
            if (isset($settings[$key])) {
                return (string) ($settings[$key]['type'] ?? '');
            }
        }

        return \is_array($value) ? json_encode($value) : '';
    }
}
