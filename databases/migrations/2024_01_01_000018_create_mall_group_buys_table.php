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

class CreateMallGroupBuysTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_buys', static function (Blueprint $table) {
            $table->comment('团购活动表');
            $table->id();
            $table->string('title', 255)->comment('活动标题');
            $table->text('description')->nullable()->comment('活动描述');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->decimal('original_price', 10, 2)->comment('原价');
            $table->decimal('group_price', 10, 2)->comment('团购价');
            $table->unsignedInteger('min_people')->default(2)->comment('最少成团人数');
            $table->unsignedInteger('max_people')->default(100)->comment('最多成团人数');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->unsignedInteger('group_time_limit')->default(24)->comment('成团时限(小时)');
            $table->enum('status', ['pending', 'active', 'ended', 'cancelled', 'sold_out'])->default('pending')->comment('活动状态');
            $table->unsignedInteger('total_quantity')->default(0)->comment('总库存');
            $table->unsignedInteger('sold_quantity')->default(0)->comment('已售数量');
            $table->unsignedInteger('group_count')->default(0)->comment('开团数量');
            $table->unsignedInteger('success_group_count')->default(0)->comment('成功成团数量');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->json('rules')->nullable()->comment('活动规则');
            $table->json('images')->nullable()->comment('活动图片');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id'], 'idx_product_id');
            $table->index(['sku_id'], 'idx_sku_id');
            $table->index(['status'], 'idx_status');
            $table->index(['is_enabled'], 'idx_is_enabled');
            $table->index(['start_time', 'end_time'], 'idx_time_range');
            $table->index(['sort_order'], 'idx_sort_order');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_buys');
    }
}
