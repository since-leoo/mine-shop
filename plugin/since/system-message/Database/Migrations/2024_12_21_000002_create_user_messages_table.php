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

class CreateUserMessagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_messages', static function (Blueprint $table) {
            $table->comment('用户消息关联表');
            $table->bigIncrements('id')->comment('主键');
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->bigInteger('message_id')->unsigned()->comment('消息ID');
            $table->boolean('is_read')->default(false)->comment('是否已读');
            $table->timestamp('read_at')->nullable()->comment('阅读时间');
            $table->boolean('is_deleted')->default(false)->comment('是否删除');
            $table->timestamp('deleted_at')->nullable()->comment('删除时间');
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');

            // 唯一索引
            $table->unique(['user_id', 'message_id'], 'uk_user_message');

            // 普通索引
            $table->index('user_id', 'idx_user_id');
            $table->index('message_id', 'idx_message_id');
            $table->index('is_read', 'idx_is_read');
            $table->index('is_deleted', 'idx_is_deleted');
            $table->index(['user_id', 'is_read'], 'idx_user_read');
            $table->index(['user_id', 'is_deleted'], 'idx_user_deleted');
            $table->index('created_at', 'idx_created_at');

            // 外键约束
            // $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            // $table->foreign('message_id')->references('id')->on('system_messages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_messages');
    }
}
