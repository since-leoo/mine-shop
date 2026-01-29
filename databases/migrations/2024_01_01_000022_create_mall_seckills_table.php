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

class CreateMallSeckillsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mall_seckills', static function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->comment('活动标题');
            $table->text('description')->nullable()->comment('活动描述');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->enum('status', ['pending', 'active', 'ended', 'cancelled', 'sold_out'])->default('pending')->comment('状态');
            $table->unsignedInteger('max_quantity_per_user')->default(1)->comment('每人限购数量');
            $table->unsignedInteger('total_quantity')->default(0)->comment('总库存');
            $table->unsignedInteger('sold_quantity')->default(0)->comment('已售数量');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->json('rules')->nullable()->comment('活动规则');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_enabled']);
            $table->index(['start_time', 'end_time']);
            $table->comment('秒杀活动表');
        });

        Schema::create('mall_seckill_products', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seckill_id')->comment('秒杀活动ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('product_sku_id')->comment('商品SKU ID');
            $table->decimal('original_price', 10, 2)->comment('原价');
            $table->decimal('seckill_price', 10, 2)->comment('秒杀价');
            $table->unsignedInteger('quantity')->default(0)->comment('库存');
            $table->unsignedInteger('sold_quantity')->default(0)->comment('已售数量');
            $table->unsignedInteger('max_quantity_per_user')->default(1)->comment('每人限购');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->index('seckill_id');
            $table->index('product_id');
            $table->index('product_sku_id');
            $table->comment('秒杀商品表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mall_seckill_products');
        Schema::dropIfExists('mall_seckills');
    }
}
