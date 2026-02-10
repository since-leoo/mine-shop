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
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AlterUserNotificationPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_notification_preferences', static function (Blueprint $table) {
            // 删除旧字段
            if (Schema::hasColumn('user_notification_preferences', 'message_type')) {
                $table->dropColumn('message_type');
            }
            if (Schema::hasColumn('user_notification_preferences', 'channels')) {
                $table->dropColumn('channels');
            }
            if (Schema::hasColumn('user_notification_preferences', 'is_enabled')) {
                $table->dropColumn('is_enabled');
            }
            if (Schema::hasColumn('user_notification_preferences', 'custom_settings')) {
                $table->dropColumn('custom_settings');
            }
            // 添加新字段
            $table->json('channel_preferences')->nullable()->comment('渠道偏好设置');
            $table->json('type_preferences')->nullable()->comment('消息类型偏好设置');
            $table->boolean('do_not_disturb_enabled')->default(false)->comment('是否启用免打扰');
            $table->tinyInteger('min_priority')->default(1)->comment('最小优先级(1-5)');
        });

        // 删除旧的唯一索引
        Schema::table('user_notification_preferences', static function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('user_notification_preferences');

            if (isset($indexes['uk_user_type'])) {
                $table->dropUnique('uk_user_type');
            }
            if (isset($indexes['idx_message_type'])) {
                $table->dropIndex('idx_message_type');
            }
            if (isset($indexes['idx_is_enabled'])) {
                $table->dropIndex('idx_is_enabled');
            }
            if (isset($indexes['idx_user_enabled'])) {
                $table->dropIndex('idx_user_enabled');
            }
        });

        // 添加新的唯一索引
        Schema::table('user_notification_preferences', static function (Blueprint $table) {
            $table->unique('user_id', 'uk_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_preferences', static function (Blueprint $table) {
            // 删除新字段
            $table->dropColumn(['channel_preferences', 'type_preferences', 'do_not_disturb_enabled', 'min_priority']);

            // 恢复旧字段
            $table->enum('message_type', ['system', 'announcement', 'alert', 'reminder'])
                ->comment('消息类型')->after('user_id');
            $table->json('channels')->nullable()->comment('启用的传递渠道')->after('message_type');
            $table->boolean('is_enabled')->default(true)->comment('是否启用')->after('channels');
            $table->json('custom_settings')->nullable()->comment('自定义设置')->after('do_not_disturb_end');
        });
    }
}
