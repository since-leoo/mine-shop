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

use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Meta;
use Hyperf\Database\Migrations\Migration;

class FixSystemMessageMenus extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 删除旧的菜单记录
        Menu::where('name', 'like', 'plugin:system:message%')->delete();

        // 重新创建菜单
        $baseData = [
            'name' => '',
            'path' => '',
            'component' => '',
            'redirect' => '',
            'created_by' => 0,
            'updated_by' => 0,
            'remark' => '',
        ];

        // 创建父菜单
        $parentMenu = Menu::create(array_merge($baseData, [
            'parent_id' => 0,
            'name' => 'plugin:system:message',
            'path' => '/admin/system-message',
            'redirect' => '/admin/system-message/list',
            'meta' => new Meta([
                'title' => '消息管理',
                'i18n' => 'plugin.systemMessage.title',
                'icon' => 'ep:message',
                'type' => 'M',
                'hidden' => false,
                'breadcrumbEnable' => true,
                'copyright' => true,
                'cache' => true,
                'affix' => false,
            ]),
            'sort' => 100,
        ]));

        // 创建子菜单
        $childMenus = [
            [
                'parent_id' => $parentMenu->id,
                'name' => 'plugin:system:message:list',
                'path' => '/admin/system-message/list',
                'component' => 'since/system-message/views/admin/AdminMessageList',
                'meta' => new Meta([
                    'title' => '消息列表',
                    'i18n' => 'plugin.systemMessage.list',
                    'icon' => 'ep:list',
                    'type' => 'M',
                    'hidden' => false,
                    'componentPath' => 'plugins/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => true,
                    'copyright' => true,
                    'cache' => true,
                    'affix' => false,
                ]),
                'sort' => 1,
            ],
            [
                'parent_id' => $parentMenu->id,
                'name' => 'plugin:system:message:statistics',
                'path' => '/admin/system-message/statistics',
                'component' => 'since/system-message/views/admin/AdminDashboard',
                'meta' => new Meta([
                    'title' => '消息统计',
                    'i18n' => 'plugin.systemMessage.statistics',
                    'icon' => 'ep:data-analysis',
                    'type' => 'M',
                    'hidden' => false,
                    'componentPath' => 'plugins/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => true,
                    'copyright' => true,
                    'cache' => true,
                    'affix' => false,
                ]),
                'sort' => 2,
            ],
            [
                'parent_id' => $parentMenu->id,
                'name' => 'plugin:system:message:settings',
                'path' => '/admin/system-message/settings',
                'component' => 'since/system-message/views/NotificationSettings',
                'meta' => new Meta([
                    'title' => '消息设置',
                    'i18n' => 'plugin.systemMessage.settings',
                    'icon' => 'ep:setting',
                    'type' => 'M',
                    'hidden' => false,
                    'componentPath' => 'plugins/',
                    'componentSuffix' => '.vue',
                    'breadcrumbEnable' => true,
                    'copyright' => true,
                    'cache' => true,
                    'affix' => false,
                ]),
                'sort' => 3,
            ],
        ];

        foreach ($childMenus as $childMenu) {
            Menu::create(array_merge($baseData, $childMenu));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::where('name', 'like', 'plugin:system:message%')->delete();
    }
}
