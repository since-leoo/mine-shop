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

use App\Infrastructure\Model\Permission\Menu;
use App\Infrastructure\Model\Permission\Meta;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;
use SinceLeoo\Plugin\Contract\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public const BASE_DATA = [
        'name' => '',
        'path' => '',
        'component' => '',
        'redirect' => '',
        'created_by' => 0,
        'updated_by' => 0,
        'remark' => '',
    ];

    protected array $frontendOverrides = [
        'web/overrides/notification.tsx' => 'web/src/layouts/components/bars/toolbar/components/notification.tsx',
    ];

    public function install(): void
    {
        $this->createMenus();
        $this->createDefaultConfig();
        $this->overrideFrontendFiles();
    }

    public function uninstall(): void
    {
        $this->restoreFrontendFiles();
        $this->removeMenus();
        $this->removePermissions();
        $this->removeConfigFile();
    }

    public function boot(): void
    {
        // 插件启动时的逻辑（如需要）
    }

    protected function overrideFrontendFiles(): void
    {
        $pluginPath = \dirname(__DIR__);
        $basePath = BASE_PATH;

        foreach ($this->frontendOverrides as $source => $target) {
            $sourceFile = $pluginPath . '/' . $source;
            $targetFile = $basePath . '/' . $target;
            $backupFile = $targetFile . '.backup';

            if (! file_exists($sourceFile)) {
                continue;
            }

            // 只在第一次安装时备份，避免重复安装覆盖原始备份
            if (file_exists($targetFile) && ! file_exists($backupFile)) {
                copy($targetFile, $backupFile);
            } elseif (file_exists($backupFile)) {
                // 备份已存在说明之前安装过，跳过备份但仍然覆盖文件
                system_message_logger()->info('Backup already exists, skipping backup', [
                    'target' => $targetFile,
                ]);
            }

            copy($sourceFile, $targetFile);
        }
    }

    protected function restoreFrontendFiles(): void
    {
        $basePath = BASE_PATH;

        foreach ($this->frontendOverrides as $source => $target) {
            $targetFile = $basePath . '/' . $target;
            $backupFile = $targetFile . '.backup';

            if (file_exists($backupFile)) {
                copy($backupFile, $targetFile);
                unlink($backupFile);
            } else {
                $pluginPath = \dirname(__DIR__);
                $originalFile = $pluginPath . '/web/overrides/notification.original.tsx';

                if (file_exists($originalFile) && file_exists($targetFile)) {
                    copy($originalFile, $targetFile);
                }
            }
        }
    }

    protected function createMenus(): void
    {
        if (! Schema::hasTable('menu')) {
            return;
        }

        if (Menu::where('name', 'plugin:system:message')->exists()) {
            return;
        }

        $parentMenu = Menu::create(array_merge(self::BASE_DATA, [
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
            Menu::create(array_merge(self::BASE_DATA, $childMenu));
        }
    }

    protected function createDefaultConfig(): void
    {
        $pluginConfigFile = \dirname(__DIR__) . '/config/system_message.php';
        $autoloadConfigFile = BASE_PATH . '/config/autoload/system_message.php';

        if (file_exists($autoloadConfigFile) || ! file_exists($pluginConfigFile)) {
            return;
        }

        $config = require $pluginConfigFile;
        $exportedConfig = var_export($config, true);
        $exportedConfig = preg_replace('/array \(/', '[', $exportedConfig);
        $exportedConfig = preg_replace('/\)$/', ']', $exportedConfig);
        $exportedConfig = preg_replace('/\),/', '],', $exportedConfig);
        $exportedConfig = preg_replace("/=> \n\\s+\\[/", '=> [', $exportedConfig);

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn {$exportedConfig};\n";
        file_put_contents($autoloadConfigFile, $content);
    }

    protected function removeMenus(): void
    {
        if (Schema::hasTable('menu')) {
            Menu::where('name', 'like', 'plugin:system:message%')->delete();
        }
    }

    protected function removePermissions(): void
    {
        if (Schema::hasTable('permissions')) {
            Db::table('permissions')
                ->where('name', 'like', 'system-message:%')
                ->delete();
        }
    }

    protected function removeConfigFile(): void
    {
        $autoloadConfigFile = BASE_PATH . '/config/autoload/system_message.php';
        if (file_exists($autoloadConfigFile)) {
            unlink($autoloadConfigFile);
        }
    }
}
