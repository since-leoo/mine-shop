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

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_delivery_logs', static function (Blueprint $table) {
            $table->comment('消息传递日志表');
            $table->bigIncrements('id')->comment('主键');
            $table->bigInteger('message_id')->unsigned()->comment('消息ID');
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->enum('channel', ['database', 'socketio', 'websocket', 'email', 'sms', 'push', 'miniapp'])
                ->comment('传递渠道');
            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])
                ->default('pending')
                ->comment('传递状态');
            $table->tinyInteger('attempt_count')->default(0)->comment('尝试次数');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->json('metadata')->nullable()->comment('元数据（扩展信息）');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamp('delivered_at')->nullable()->comment('送达时间');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');

            // 索引
            $table->index('message_id', 'idx_message_id');
            $table->index('user_id', 'idx_user_id');
            $table->index('channel', 'idx_channel');
            $table->index('status', 'idx_status');
            $table->index(['message_id', 'user_id'], 'idx_message_user');
            $table->index(['channel', 'status'], 'idx_channel_status');
            $table->index('attempt_count', 'idx_attempt_count');
            $table->index('sent_at', 'idx_sent_at');
            $table->index('delivered_at', 'idx_delivered_at');
            $table->index('created_at', 'idx_created_at');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_delivery_logs');
    }
};
