<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallSeckillOrdersTable extends Migration
{
    public function up(): void
    {
        Schema::create('seckill_orders', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('seckill_id')->comment('秒杀活动ID');
            $table->unsignedBigInteger('seckill_product_id')->comment('秒杀商品ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
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
            $table->index('seckill_id');
            $table->index('seckill_product_id');
            $table->index('member_id');
            $table->index('status');
            $table->index(['seckill_id', 'member_id']);

            $table->comment('秒杀订单表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seckill_orders');
    }
}
