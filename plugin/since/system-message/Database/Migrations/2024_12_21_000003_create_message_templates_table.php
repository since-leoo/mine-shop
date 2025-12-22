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

class CreateMessageTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_templates', static function (Blueprint $table) {
            $table->comment('消息模板表');
            $table->bigIncrements('id')->comment('模板ID，主键');
            $table->string('name', 100)->comment('模板名称');
            $table->string('title', 255)->comment('消息标题模板');
            $table->text('content')->comment('消息内容模板');
            $table->enum('type', ['system', 'announcement', 'alert', 'reminder'])
                ->default('system')
                ->comment('消息类型');
            $table->enum('format', ['text', 'html', 'markdown'])
                ->default('text')
                ->comment('内容格式');
            $table->json('variables')->nullable()->comment('可用变量列表');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->bigInteger('created_by')->unsigned()->nullable()->comment('创建者');
            $table->bigInteger('updated_by')->unsigned()->nullable()->comment('更新者');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('删除时间');
            $table->string('remark', 255)->default('')->comment('备注');
            
            // 索引
            $table->index('name', 'idx_name');
            $table->index('type', 'idx_type');
            $table->index('is_active', 'idx_is_active');
            $table->index('format', 'idx_format');
            $table->index(['type', 'is_active'], 'idx_type_active');
            $table->index('created_by', 'idx_created_by');
            $table->index('deleted_at', 'idx_deleted_at');
            
            // 外键约束
            // $table->foreign('created_by')->references('id')->on('user')->onDelete('set null');
            // $table->foreign('updated_by')->references('id')->on('user')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
}