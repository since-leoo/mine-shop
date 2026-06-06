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
        Schema::create('diy_pages', static function (Blueprint $table): void {
            $table->id();
            $table->string('page_key', 64)->comment('页面键，如 home');
            $table->string('title', 100)->comment('页面名称');
            $table->string('page_type', 32)->default('miniprogram')->comment('页面类型：miniprogram,h5,all');
            $table->string('description', 255)->nullable()->comment('页面说明');
            $table->boolean('is_enabled')->default(false)->comment('是否启用');
            $table->string('status', 32)->default('draft')->comment('状态：draft,published,disabled');
            $table->unsignedBigInteger('published_version_id')->nullable()->comment('当前发布版本ID');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新人');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['page_key', 'page_type', 'is_enabled']);
            $table->index(['page_type', 'status']);
            $table->comment('DIY装修页面表');
        });

        Schema::create('diy_page_versions', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('page_id')->comment('页面ID');
            $table->unsignedInteger('version_no')->default(1)->comment('版本号');
            $table->string('status', 32)->default('draft')->comment('状态：draft,published,archived');
            $table->json('schema')->comment('页面结构');
            $table->timestamp('published_at')->nullable()->comment('发布时间');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();

            $table->index(['page_id', 'status']);
            $table->unique(['page_id', 'version_no']);
            $table->comment('DIY装修页面版本表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diy_page_versions');
        Schema::dropIfExists('diy_pages');
    }
};
