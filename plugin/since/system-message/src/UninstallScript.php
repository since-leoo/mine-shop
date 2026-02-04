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
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

class UninstallScript
{
    /**
     * 需要恢复的前端文件映射
     * 格式: [目标文件(web目录)].
     */
    protected array $frontendOverrides = [
        'web/src/layouts/components/bars/toolbar/components/notification.tsx',
    ];

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 插件卸载脚本.
     *
     * @param bool $keepData 是否保留数据
     */
    public function __invoke(bool $keepData = true): bool
    {
        try {
            // 恢复前端文件
            $this->restoreFrontendFiles();

            // 清理缓存
            $this->clearCache();

            // 停止队列任务
            $this->stopQueueJobs();

            // 删除菜单
            $this->removeMenus();

            // 删除权限
            $this->removePermissions();

            // 删除配置文件
            $this->removeConfigFile();

            // 根据选择决定是否删除数据
            if (! $keepData) {
                $this->removeData();
            } else {
                // 只是标记为已删除，保留数据
                $this->markAsDeleted();
            }

            return true;
        } catch (\Throwable $e) {
            // 记录错误日志
            system_message_logger()->error('System Message Plugin uninstallation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'keep_data' => $keepData,
            ]);

            return false;
        }
    }

    /**
     * 恢复前端文件.
     */
    protected function restoreFrontendFiles(): void
    {
        $basePath = BASE_PATH;

        foreach ($this->frontendOverrides as $target) {
            $targetFile = $basePath . '/' . $target;
            $backupFile = $targetFile . '.backup';

            // 如果备份文件存在，恢复它
            if (file_exists($backupFile)) {
                if (copy($backupFile, $targetFile)) {
                    unlink($backupFile);
                    system_message_logger()->info("Restored frontend file: {$targetFile}");
                } else {
                    system_message_logger()->error("Failed to restore frontend file: {$targetFile}");
                }
            } else {
                // 如果没有备份，使用插件自带的原始文件恢复
                $pluginPath = \dirname(__DIR__);
                $originalFile = $pluginPath . '/web/overrides/notification.original.tsx';

                if (file_exists($originalFile) && file_exists($targetFile)) {
                    if (copy($originalFile, $targetFile)) {
                        system_message_logger()->info("Restored frontend file from original: {$targetFile}");
                    }
                }
            }
        }
    }

    /**
     * 删除菜单.
     */
    protected function removeMenus(): void
    {
        try {
            if (Schema::hasTable('menu')) {
                Menu::where('name', 'like', 'plugin:system:message%')->delete();
                system_message_logger()->info('Removed system message menus');
            }
        } catch (\Throwable $e) {
            system_message_logger()->warning('Failed to remove menus during uninstall: ' . $e->getMessage());
        }
    }

    /**
     * 清理缓存.
     */
    protected function clearCache(): void
    {
        try {
            // 清理系统消息相关的缓存
            $cache = $this->container->get(CacheInterface::class);

            // 清理消息缓存
            $cache->delete('system_message:*');

            // 清理用户偏好缓存
            $cache->delete('user_preferences:*');

            // 清理模板缓存
            $cache->delete('message_templates:*');
        } catch (\Throwable $e) {
            system_message_logger()->warning('Failed to clear cache during uninstall: ' . $e->getMessage());
        }
    }

    /**
     * 停止队列任务
     */
    protected function stopQueueJobs(): void
    {
        try {
            // 清理待处理的队列任务
            $redis = $this->container->get(RedisFactory::class)->get('default');

            // 清理系统消息队列
            $redis->del('system_message:waiting');
            $redis->del('system_message:reserved');
            $redis->del('system_message:delayed');
            $redis->del('system_message:failed');
        } catch (\Throwable $e) {
            system_message_logger()->warning('Failed to stop queue jobs during uninstall: ' . $e->getMessage());
        }
    }

    /**
     * 删除权限.
     */
    protected function removePermissions(): void
    {
        try {
            // 删除权限
            if (Schema::hasTable('permissions')) {
                Db::table('permissions')
                    ->where('name', 'like', 'system-message:%')
                    ->delete();
            }

            // 删除角色权限关联
            if (Schema::hasTable('role_has_permissions')) {
                $permissionIds = Db::table('permissions')
                    ->where('name', 'like', 'system-message:%')
                    ->pluck('id');

                if ($permissionIds->isNotEmpty()) {
                    Db::table('role_has_permissions')
                        ->whereIn('permission_id', $permissionIds)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            system_message_logger()->warning('Failed to remove permissions during uninstall: ' . $e->getMessage());
        }
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
     * 删除所有数据.
     */
    protected function removeData(): void
    {
        try {
            // 按依赖关系顺序删除表
            $tables = [
                'message_delivery_logs',
                'user_notification_preferences',
                'user_messages',
                'message_templates',
                'system_messages',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    Schema::drop($table);
                    system_message_logger()->info("Dropped table: {$table}");
                }
            }
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to remove data during uninstall: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 标记为已删除（软删除）.
     */
    protected function markAsDeleted(): void
    {
        try {
            $now = date('Y-m-d H:i:s');

            // 软删除消息
            if (Schema::hasTable('system_messages')) {
                Db::table('system_messages')
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $now]);
            }

            // 软删除模板
            if (Schema::hasTable('message_templates')) {
                Db::table('message_templates')
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $now]);
            }

            // 标记用户消息为已删除
            if (Schema::hasTable('user_messages')) {
                Db::table('user_messages')
                    ->where('is_deleted', false)
                    ->update([
                        'is_deleted' => true,
                        'deleted_at' => $now,
                    ]);
            }

            system_message_logger()->info('System message data marked as deleted (soft delete)');
        } catch (\Throwable $e) {
            system_message_logger()->error('Failed to mark data as deleted during uninstall: ' . $e->getMessage());
            throw $e;
        }
    }
}
