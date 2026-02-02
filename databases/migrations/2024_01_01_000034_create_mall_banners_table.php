<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->comment('轮播图标题');
            $table->string('image')->comment('轮播图图片');
            $table->string('link')->nullable()->comment('跳转链接');
            $table->string('link_type', 20)->default('none')->comment('链接类型：none,url,product,category');
            $table->unsignedBigInteger('link_id')->nullable()->comment('关联ID');
            $table->string('position', 50)->default('home')->comment('展示位置：home,category,product');
            $table->string('status', 20)->default('active')->comment('状态：active,inactive');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamp('start_time')->nullable()->comment('开始时间');
            $table->timestamp('end_time')->nullable()->comment('结束时间');
            $table->integer('click_count')->default(0)->comment('点击次数');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('position');
            $table->index('status');
            $table->index(['start_time', 'end_time']);
            $table->comment('轮播图表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
