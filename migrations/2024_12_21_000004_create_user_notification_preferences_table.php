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

class CreateUserNotificationPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_notification_preferences', static function (Blueprint $table) {
            $table->comment('用户通知偏好表');
            $table->bigIncrements('id')->comment('主键');
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->enum('message_type', ['system', 'announcement', 'alert', 'reminder'])
                ->comment('消息类型');
            $table->json('channels')->nullable()->comment('启用的传递渠道');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->time('do_not_disturb_start')->nullable()->comment('免打扰开始时间');
            $table->time('do_not_disturb_end')->nullable()->comment('免打扰结束时间');
            $table->json('custom_settings')->nullable()->comment('自定义设置（为未来扩展预留）');
            $table->timestamps();

            // 唯一索引
            $table->unique(['user_id', 'message_type'], 'uk_user_type');

            // 普通索引
            $table->index('user_id', 'idx_user_id');
            $table->index('message_type', 'idx_message_type');
            $table->index('is_enabled', 'idx_is_enabled');
            $table->index(['user_id', 'is_enabled'], 'idx_user_enabled');

            // 外键约束
            // $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
}
