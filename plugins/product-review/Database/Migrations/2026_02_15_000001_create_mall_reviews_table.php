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
        Schema::create('reviews', static function (Blueprint $table) {
            $table->comment('商品评价表');
            $table->id()->comment('主键ID');
            $table->unsignedBigInteger('order_id')->comment('关联订单ID');
            $table->unsignedBigInteger('order_item_id')->comment('关联订单项ID');
            $table->unsignedBigInteger('product_id')->comment('关联商品ID');
            $table->unsignedBigInteger('sku_id')->comment('关联SKU ID');
            $table->unsignedBigInteger('member_id')->comment('评价用户ID');
            $table->unsignedTinyInteger('rating')->comment('评分1-5');
            $table->text('content')->comment('评价内容');
            $table->json('images')->nullable()->comment('评价图片URL列表');
            $table->boolean('is_anonymous')->default(false)->comment('是否匿名');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->comment('审核状态');
            $table->text('admin_reply')->nullable()->comment('管理员回复');
            $table->timestamp('reply_time')->nullable()->comment('回复时间');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建者ID');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('更新者ID');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->timestamp('deleted_at')->nullable()->comment('删除时间');

            $table->index(['order_id'], 'idx_order_id');
            $table->index(['order_item_id'], 'idx_order_item_id');
            $table->index(['product_id'], 'idx_product_id');
            $table->index(['member_id'], 'idx_member_id');
            $table->index(['status'], 'idx_status');
            $table->index(['product_id', 'status', 'created_at'], 'idx_product_status_created');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
