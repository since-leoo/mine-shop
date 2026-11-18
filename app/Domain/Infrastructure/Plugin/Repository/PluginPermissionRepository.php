<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Repository;

use App\Infrastructure\Error\PluginError;

use App\Infrastructure\Model\Permission\Menu;

final class PluginPermissionRepository
{
    public function register(string $group, array $permissions): void
    {
        // 同一组由唯一 name 约束串行化；保留管理员对状态、角色和旧权限的配置。
        $groupName = 'plugin:' . $group;
        Menu::query()->insertOrIgnore([
            'name' => $groupName, 'parent_id' => 0,
            'meta' => json_encode(['title' => $group, 'i18n' => '', 'type' => 'M', 'hidden' => true], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        $parent = Menu::query()->where('name', $groupName)->lockForUpdate()->firstOrFail();
        foreach ($permissions as $code => $title) {
            Menu::query()->insertOrIgnore([
                'name' => $code, 'parent_id' => $parent->id,
                'meta' => json_encode(['title' => $title, 'i18n' => '', 'type' => 'B'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ]);
            $menu = Menu::query()->where('name', $code)->lockForUpdate()->firstOrFail();
            if ((int) $menu->parent_id !== (int) $parent->id) {
                throw PluginError::PERMISSION_CODE_CONFLICT->exception();
            }
            $meta = $menu->meta->toArray();
            $meta['title'] = $title;
            $menu->meta = $meta;
            $menu->save();
        }
    }
}
