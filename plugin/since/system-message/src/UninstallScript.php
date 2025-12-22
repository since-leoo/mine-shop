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

use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;

class UninstallScript
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 插件卸载脚本
     * 
     * @param bool $keepData 是否保留数据
     */
    public function __invoke(bool $keepData = true): bool
    {
        try {
            // 清理缓存
            $this->clearCache();
            
            // 停止队列任务
            $this->stopQueueJobs();
            
            // 删除权限和菜单
            $this->removePermissionsAndMenus();
            
            // 根据选择决定是否删除数据
            if (!$keepData) {
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
     * 清理缓存
     */
    protected function clearCache(): void
    {
        try {
            // 清理系统消息相关的缓存
            $cache = $this->container->get(\Psr\SimpleCache\CacheInterface::class);
            
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
            $redis = $this->container->get(\Hyperf\Redis\RedisFactory::class)->get('default');
            
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
     * 删除权限和菜单
     */
    protected function removePermissionsAndMenus(): void
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
                
                if (!empty($permissionIds)) {
                    Db::table('role_has_permissions')
                        ->whereIn('permission_id', $permissionIds)
                        ->delete();
                }
            }

            // 删除菜单
            if (Schema::hasTable('menu')) {
                // 获取系统消息菜单ID
                $menuIds = Db::table('menu')
                    ->where('name', 'like', 'plugin:system:message%')
                    ->pluck('id');

                if (!empty($menuIds)) {
                    // 删除菜单
                    Db::table('menu')
                        ->whereIn('id', $menuIds)
                        ->delete();

                    // 删除角色菜单关联
                    if (Schema::hasTable('role_has_menus')) {
                        Db::table('role_has_menus')
                            ->whereIn('menu_id', $menuIds)
                            ->delete();
                    }
                }
            }

        } catch (\Throwable $e) {
            system_message_logger()->warning('Failed to remove permissions and menus during uninstall: ' . $e->getMessage());
        }
    }

    /**
     * 删除所有数据
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
     * 标记为已删除（软删除）
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

    /**
     * 获取数据统计信息
     */
    public function getDataStats(): array
    {
        $stats = [];

        try {
            if (Schema::hasTable('system_messages')) {
                $stats['messages'] = Db::table('system_messages')
                    ->whereNull('deleted_at')
                    ->count();
            }

            if (Schema::hasTable('message_templates')) {
                $stats['templates'] = Db::table('message_templates')
                    ->whereNull('deleted_at')
                    ->count();
            }

            if (Schema::hasTable('user_messages')) {
                $stats['user_messages'] = Db::table('user_messages')
                    ->where('is_deleted', false)
                    ->count();
            }

            if (Schema::hasTable('user_notification_preferences')) {
                $stats['preferences'] = Db::table('user_notification_preferences')
                    ->count();
            }

            if (Schema::hasTable('message_delivery_logs')) {
                $stats['delivery_logs'] = Db::table('message_delivery_logs')
                    ->count();
            }

        } catch (\Throwable $e) {
            system_message_logger()->warning('Failed to get data stats: ' . $e->getMessage());
        }

        return $stats;
    }

    /**
     * 检查是否可以安全卸载
     */
    public function canSafelyUninstall(): array
    {
        $issues = [];

        try {
            // 检查是否有待发送的消息
            if (Schema::hasTable('system_messages')) {
                $pendingMessages = Db::table('system_messages')
                    ->whereIn('status', ['draft', 'scheduled', 'sending'])
                    ->whereNull('deleted_at')
                    ->count();

                if ($pendingMessages > 0) {
                    $issues[] = "有 {$pendingMessages} 条消息尚未发送完成";
                }
            }

            // 检查是否有正在处理的队列任务
            $redis = $this->container->get(\Hyperf\Redis\RedisFactory::class)->get('default');
            $queueLength = $redis->llen('system_message:waiting');
            
            if ($queueLength > 0) {
                $issues[] = "有 {$queueLength} 个队列任务正在等待处理";
            }

        } catch (\Throwable $e) {
            $issues[] = '无法检查系统状态：' . $e->getMessage();
        }

        return $issues;
    }
}