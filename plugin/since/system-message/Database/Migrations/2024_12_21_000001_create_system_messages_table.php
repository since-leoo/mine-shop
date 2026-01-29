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

class CreateSystemMessagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_messages', static function (Blueprint $table) {
            $table->comment('系统消息表');
            $table->bigIncrements('id')->comment('消息ID，主键');
            $table->string('title', 255)->comment('消息标题');
            $table->text('content')->comment('消息内容');
            $table->enum('type', ['system', 'announcement', 'alert', 'reminder'])
                ->default('system')
                ->comment('消息类型');
            $table->tinyInteger('priority')->default(1)->comment('优先级 1-5');
            $table->bigInteger('sender_id')->unsigned()->nullable()->comment('发送者ID');
            $table->enum('recipient_type', ['all', 'role', 'user'])
                ->default('all')
                ->comment('收件人类型');
            $table->json('recipient_ids')->nullable()->comment('收件人ID列表');
            $table->bigInteger('template_id')->unsigned()->nullable()->comment('模板ID');
            $table->json('template_variables')->nullable()->comment('模板变量');
            $table->json('channels')->nullable()->comment('传递渠道');
            $table->timestamp('scheduled_at')->nullable()->comment('计划发送时间');
            $table->timestamp('sent_at')->nullable()->comment('实际发送时间');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])
                ->default('draft')
                ->comment('状态');
            $table->bigInteger('created_by')->unsigned()->nullable()->comment('创建者');
            $table->bigInteger('updated_by')->unsigned()->nullable()->comment('更新者');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('删除时间');
            $table->string('remark', 255)->default('')->comment('备注');

            // 索引
            $table->index('type', 'idx_type');
            $table->index('status', 'idx_status');
            $table->index('scheduled_at', 'idx_scheduled_at');
            $table->index('created_at', 'idx_created_at');
            $table->index('sender_id', 'idx_sender_id');
            $table->index('recipient_type', 'idx_recipient_type');
            $table->index(['type', 'status'], 'idx_type_status');
            $table->index('deleted_at', 'idx_deleted_at');

            // 外键约束（如果需要）
            // $table->foreign('sender_id')->references('id')->on('user')->onDelete('set null');
            // $table->foreign('template_id')->references('id')->on('message_templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_messages');
    }
}
