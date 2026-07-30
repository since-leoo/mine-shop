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
    public function up(): void
    {
        Schema::create('diy_page_publish_records', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id')->comment('页面ID');
            $table->unsignedBigInteger('version_id')->nullable()->comment('版本ID');
            $table->string('publish_type', 32)->default('manual')->comment('发布类型：manual,scheduled,rollback');
            $table->string('publish_status', 32)->default('pending')->comment('发布状态：pending,published,failed,cancelled');
            $table->timestamp('scheduled_at')->nullable()->comment('定时发布时间');
            $table->timestamp('published_at')->nullable()->comment('实际发布时间');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->string('error_message', 500)->nullable()->comment('失败原因');
            $table->timestamps();

            $table->index(['page_id', 'publish_status']);
            $table->index(['version_id', 'publish_status']);
            $table->index(['publish_status', 'scheduled_at']);
            $table->comment('DIY装修页面发布记录表');
        });

        Schema::create('diy_page_preview_tokens', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id')->comment('页面ID');
            $table->unsignedBigInteger('version_id')->nullable()->comment('版本ID');
            $table->string('token', 64)->comment('预览令牌');
            $table->timestamp('expired_at')->comment('过期时间');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人ID');
            $table->timestamps();

            $table->unique('token');
            $table->index(['page_id', 'version_id']);
            $table->index('expired_at');
            $table->comment('DIY装修页面预览令牌表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diy_page_preview_tokens');
        Schema::dropIfExists('diy_page_publish_records');
    }
};
