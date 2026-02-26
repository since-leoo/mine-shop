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
        Schema::create('export_tasks', static function (Blueprint $table) {
            $table->comment('导出任务表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->string('task_name', 255)->comment('任务名称');
            $table->string('dto_class', 255)->comment('DTO类名（带 ExportColumn 注解）');
            $table->string('export_format', 20)->default('excel')->comment('导出格式 (excel|csv)');
            $table->json('export_params')->nullable()->comment('导出参数');
            $table->string('status', 20)->default('pending')->comment('状态 (pending|processing|success|failed|expired)');
            $table->tinyInteger('progress')->unsigned()->nullable()->comment('进度 (0-100)');
            $table->string('file_path', 500)->nullable()->comment('文件路径');
            $table->bigInteger('file_size')->unsigned()->nullable()->comment('文件大小(字节)');
            $table->string('file_name', 255)->nullable()->comment('文件名称');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->tinyInteger('retry_count')->unsigned()->default(0)->comment('重试次数');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->timestamp('started_at')->nullable()->comment('开始处理时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable()->comment('删除时间');

            // 索引
            $table->index('user_id', 'idx_user_id');
            $table->index('status', 'idx_status');
            $table->index('dto_class', 'idx_dto_class');
            $table->index('created_at', 'idx_created_at');
            $table->index('expired_at', 'idx_expired_at');
        });

        Schema::create('export_download_logs', static function (Blueprint $table) {
            $table->comment('导出下载日志表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->bigInteger('task_id')->unsigned()->comment('任务ID');
            $table->bigInteger('user_id')->unsigned()->comment('用户ID');
            $table->string('ip_address', 45)->nullable()->comment('IP地址');
            $table->string('user_agent', 500)->nullable()->comment('用户代理');
            $table->timestamp('downloaded_at')->useCurrent()->comment('下载时间');

            // 索引
            $table->index('task_id', 'idx_task_id');
            $table->index('user_id', 'idx_user_id');
            $table->index('downloaded_at', 'idx_downloaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_download_logs');
        Schema::dropIfExists('export_tasks');
    }
};
