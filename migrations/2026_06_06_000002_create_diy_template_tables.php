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
        Schema::create('diy_template_categories', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->comment('分类名称');
            $table->string('code', 64)->comment('分类编码');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('code');
            $table->index(['is_enabled', 'sort']);
            $table->comment('DIY装修模板分类表');
        });

        Schema::create('diy_templates', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->comment('模板分类ID');
            $table->string('name', 100)->comment('模板名称');
            $table->string('page_key', 64)->comment('页面键');
            $table->string('page_type', 32)->default('all')->comment('页面类型：miniprogram,h5,all');
            $table->string('cover', 255)->nullable()->comment('封面图');
            $table->string('description', 255)->nullable()->comment('模板说明');
            $table->json('schema')->comment('模板页面结构');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_enabled']);
            $table->index(['page_key', 'page_type', 'is_enabled']);
            $table->comment('DIY装修模板表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diy_templates');
        Schema::dropIfExists('diy_template_categories');
    }
};
