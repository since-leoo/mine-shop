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

use App\Infrastructure\Permission\Model\Menu;
use App\Infrastructure\Permission\Model\Meta;
use Hyperf\Command\Concerns\InteractsWithIO;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

class InstallScript
{
    use InteractsWithIO;

    public const BASE_DATA = [
        'name' => '',
        'path' => '',
        'component' => '',
        'redirect' => '',
        'created_by' => 0,
        'updated_by' => 0,
        'remark' => '',
    ];

    /**
     * 需要覆盖的前端文件映射
     * 格式: [源文件(插件目录) => 目标文件(web目录)].
     */
    protected array $frontendOverrides = [
        'web/overrides/notification.tsx' => 'web/src/layouts/components/bars/toolbar/components/notification.tsx',
    ];

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 插件安装脚本.
     */
    public function __invoke(): bool
    {
        try {
            // 创建菜单
            $this->createMenus();

            // 创建默认配置
            $this->createDefaultConfig();

            // 覆盖前端文件
            $this->overrideFrontendFiles();

            return true;
        } catch (\Throwable $e) {
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
     * 覆盖前端文件.
     */
    protected function overrideFrontendFiles(): void
    {
        $pluginPath = \dirname(__DIR__);
        $basePath = BASE_PATH;

        foreach ($this->frontendOverrides as $source => $target) {
            $sourceFile = $pluginPath . '/' . $source;
            $targetFile = $basePath . '/' . $target;
            $backupFile = $targetFile . '.backup';

            // 检查源文件是否存在
            if (! file_exists($sourceFile)) {
                system_message_logger()->warning("Source file not found: {$sourceFile}");
                continue;
            }

            // 备份原始文件（如果存在且未备份）
            if (file_exists($targetFile) && ! file_exists($backupFile)) {
                copy($targetFile, $backupFile);
                system_message_logger()->info("Backed up: {$targetFile} -> {$backupFile}");
            }

            // 复制新文件
            if (copy($sourceFile, $targetFile)) {
                system_message_logger()->info("Overridden: {$targetFile}");
            } else {
                system_message_logger()->error("Failed to override: {$targetFile}");
            }
        }
    }

    /**
     * 恢复前端文件.
     */
    protected function restoreFrontendFiles(): void
    {
        $basePath = BASE_PATH;

        foreach ($this->frontendOverrides as $source => $target) {
            $targetFile = $basePath . '/' . $target;
            $backupFile = $targetFile . '.backup';

            // 如果备份文件存在，恢复它
            if (file_exists($backupFile)) {
                if (copy($backupFile, $targetFile)) {
                    unlink($backupFile);
                    system_message_logger()->info("Restored: {$targetFile}");
                } else {
                    system_message_logger()->error("Failed to restore: {$targetFile}");
                }
            }
        }
    }

    /**
     * 创建菜单.
     */
    protected function createMenus(): void
    {
        // 检查菜单表是否存在
        if (! Schema::hasTable('menu')) {
            return;
        }

        // 检查是否已存在
        $exists = Menu::where('name', 'plugin:system:message')->exists();
        if ($exists) {
            return;
        }

        // 创建父菜单
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
            Menu::create(array_merge(self::BASE_DATA, $childMenu));
        }
    }

    /**
     * 创建默认配置
     * 将插件配置写入 config/autoload/system_message.php.
     */
    protected function createDefaultConfig(): void
    {
        $pluginConfigFile = \dirname(__DIR__) . '/config/system_message.php';
        $autoloadConfigFile = BASE_PATH . '/config/autoload/system_message.php';

        // 如果目标配置文件已存在，跳过
        if (file_exists($autoloadConfigFile)) {
            system_message_logger()->info("Config file already exists: {$autoloadConfigFile}");
            return;
        }

        // 检查插件配置文件是否存在
        if (! file_exists($pluginConfigFile)) {
            system_message_logger()->warning("Plugin config file not found: {$pluginConfigFile}");
            return;
        }

        // 读取插件配置
        $config = require $pluginConfigFile;

        // 生成配置文件内容
        $configContent = $this->generateConfigFileContent($config);

        // 写入配置文件
        if (file_put_contents($autoloadConfigFile, $configContent) !== false) {
            system_message_logger()->info("Config file created: {$autoloadConfigFile}");
        } else {
            throw new \RuntimeException("Failed to create config file: {$autoloadConfigFile}");
        }
    }

    /**
     * 生成配置文件内容.
     */
    protected function generateConfigFileContent(array $config): string
    {
        $exportedConfig = var_export($config, true);

        // 美化数组格式
        $exportedConfig = preg_replace('/array \(/', '[', $exportedConfig);
        $exportedConfig = preg_replace('/\)$/', ']', $exportedConfig);
        $exportedConfig = preg_replace('/\),/', '],', $exportedConfig);
        $exportedConfig = preg_replace("/=> \n\\s+\\[/", '=> [', $exportedConfig);

        return <<<PHP
            <?php

            declare(strict_types=1);
            /**
             * This file is part of MineAdmin.
             *
             * @link     https://www.mineadmin.com
             * @document https://doc.mineadmin.com
             * @contact  root@imoi.cn
             * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
             * 
             * System Message Plugin Configuration
             * Generated by InstallScript
             */

            return {$exportedConfig};

            PHP;
    }

    /**
     * 删除配置文件.
     */
    protected function removeConfigFile(): void
    {
        $autoloadConfigFile = BASE_PATH . '/config/autoload/system_message.php';

        if (file_exists($autoloadConfigFile)) {
            if (unlink($autoloadConfigFile)) {
                system_message_logger()->info("Config file removed: {$autoloadConfigFile}");
            } else {
                system_message_logger()->error("Failed to remove config file: {$autoloadConfigFile}");
            }
        }
    }

    /**
     * 回滚安装.
     */
    protected function rollback(): void
    {
        try {
            // 恢复前端文件
            $this->restoreFrontendFiles();

            // 删除配置文件
            $this->removeConfigFile();

            // 删除菜单
            if (Schema::hasTable('menu')) {
                Menu::where('name', 'like', 'plugin:system:message%')->delete();
            }

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
        } catch (\Throwable $e) {
            system_message_logger()->error('System Message Plugin rollback failed: ' . $e->getMessage());
        }
    }
}
