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

class CreateMallSeckillSessionOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_seckill_session_orders', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('activity_id')->comment('活动ID');
            $table->unsignedBigInteger('session_id')->comment('场次ID');
            $table->unsignedBigInteger('seckill_product_id')->comment('秒杀商品ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('product_sku_id')->comment('SKU ID');
            $table->unsignedInteger('quantity')->default(1)->comment('购买数量');
            $table->decimal('original_price', 10, 2)->comment('原价');
            $table->decimal('seckill_price', 10, 2)->comment('秒杀价');
            $table->decimal('total_amount', 10, 2)->comment('总金额');
            $table->string('status', 30)->default('pending')->comment('订单状态');
            $table->timestamp('seckill_time')->nullable()->comment('秒杀时间');
            $table->timestamp('pay_time')->nullable()->comment('支付时间');
            $table->timestamp('cancel_time')->nullable()->comment('取消时间');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->timestamps();

            $table->index('order_id');
            $table->index('activity_id');
            $table->index('session_id');
            $table->index('seckill_product_id');
            $table->index('member_id');
            $table->index(['session_id', 'member_id']);
            $table->comment('秒杀场次订单表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_seckill_session_orders');
    }
}
