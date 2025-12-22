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

namespace Plugin\Since\SystemMessage;

use App\Model\Permission\Menu;
use App\Model\Permission\Meta;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Concerns\InteractsWithIO;

class InstallScript
{
    use InteractsWithIO;

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 插件安装脚本
     */
    public function __invoke(): bool
    {
        try {
            // 创建默认配置
            $this->createDefaultConfig();
            
            // 创建菜单
            $this->createMenus();
            
            return true;
        } catch (\Throwable $e) {

            var_dump($e->getMessage());
            // 记录错误日志
            system_message_logger()->error('System Message Plugin installation failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            
            // 回滚已创建的内容
            $this->rollback();
            
            return false;
        }
    }

    /**
     * 创建默认配置
     */
    protected function createDefaultConfig(): void
    {
        // 这里可以创建一些默认的系统配置
        // 例如默认的通知设置、消息保留策略等
    }

    /**
     * 创建菜单
     */
    protected function createMenus(): void
    {
        // 检查菜单表是否存在
        if (!Schema::hasTable('menu')) {
            return;
        }

        // 简化的菜单数据结构 - 只包含必要的三个菜单
        $menuData = [
            'name' => 'plugin:system:message',
            'path' => '/admin/system-message',
            'component' => '',
            'redirect' => '/admin/system-message/list',
            'meta' => new Meta([
                'title' => '消息管理',
                'i18n' => 'plugin.systemMessage.title',
                'icon' => 'ep:message',
                'type' => 'M',
                'hidden' => false,
                'componentPath' => 'plugins/',
                'componentSuffix' => '.vue',
                'breadcrumbEnable' => true,
                'copyright' => true,
                'cache' => true,
                'affix' => false,
            ]),
            'children' => [
                [
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
                        'auth' => ['system-message:list'],
                    ]),
                ],
                [
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
                        'auth' => ['system-message:dashboard'],
                    ]),
                ],
                [
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
                        'auth' => ['system-message:settings'],
                    ]),
                ],
            ],
        ];

        // 创建菜单
        $this->createMenuRecursive([$menuData]);
    }

    /**
     * 递归创建菜单
     */
    protected function createMenuRecursive(array $menuData, int $parentId = 0): void
    {
        foreach ($menuData as $menu) {
            $children = $menu['children'] ?? [];
            unset($menu['children']);
            
            $menu['parent_id'] = $parentId;
            $menu['created_by'] = 1;
            $menu['updated_by'] = 1;
            $menu['remark'] = $menu['meta']->title ?? '';
            
            // 检查菜单是否已存在
            $existingMenu = Menu::where('name', $menu['name'])->first();
            if ($existingMenu) {
                $menuId = $existingMenu->id;
            } else {
                $createdMenu = Menu::create($menu);
                $menuId = $createdMenu->id;
            }
            
            // 递归创建子菜单
            if (!empty($children)) {
                $this->createMenuRecursive($children, $menuId);
            }
        }
    }

    /**
     * 获取迁移类名
     */
    protected function getMigrationClassName(string $filename): string
    {
        // 从文件名提取类名
        $parts = explode('_', $filename);
        array_shift($parts); // 移除日期部分
        array_shift($parts);
        array_shift($parts);
        
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst(str_replace('.php', '', $part));
        }
        
        return $className;
    }

    /**
     * 回滚安装
     */
    protected function rollback(): void
    {
        try {
            // 删除创建的表
            $tables = [
                'message_delivery_logs',
                'user_notification_preferences',
                'message_templates',
                'user_messages',
                'system_messages',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::drop($table);
                }
            }

            // 删除权限
            if (Schema::hasTable('permissions')) {
                Db::table('permissions')
                    ->where('name', 'like', 'system-message:%')
                    ->delete();
            }

            // 删除菜单（先删除子菜单，再删除父菜单）
            if (Schema::hasTable('menu')) {
                // 删除插件相关的所有菜单
                Menu::where('name', 'like', 'plugin:system:message%')->delete();
            }
        } catch (\Throwable $e) {
            system_message_logger()->error('System Message Plugin rollback failed: ' . $e->getMessage());
        }
    }
}