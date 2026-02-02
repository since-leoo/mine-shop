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

class CreateMallOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', static function (Blueprint $table) {
            $table->comment('订单表');
            $table->id();
            $table->string('order_no', 32)->unique()->comment('订单号');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->enum('order_type', ['normal', 'seckill', 'group_buy'])->default('normal')->comment('订单类型');
            $table->enum('status', ['pending', 'paid', 'shipped', 'partial_shipped', 'completed', 'cancelled', 'refunded'])->default('pending')->comment('订单状态');
            $table->decimal('goods_amount', 10, 2)->comment('商品金额');
            $table->decimal('shipping_fee', 10, 2)->default(0)->comment('运费');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('优惠金额');
            $table->decimal('total_amount', 10, 2)->comment('订单总金额');
            $table->decimal('pay_amount', 10, 2)->nullable()->comment('实付金额');
            $table->enum('pay_status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending')->comment('支付状态');
            $table->timestamp('pay_time')->nullable()->comment('支付时间');
            $table->string('pay_no', 64)->nullable()->comment('支付流水号');
            $table->enum('pay_method', ['wechat', 'alipay', 'balance'])->nullable()->comment('支付方式');
            $table->text('buyer_remark')->nullable()->comment('买家备注');
            $table->text('seller_remark')->nullable()->comment('卖家备注');
            $table->enum('shipping_status', ['pending', 'partial_shipped', 'shipped', 'delivered'])->default('pending')->comment('发货状态');
            $table->unsignedInteger('package_count')->default(0)->comment('包裹数量');
            $table->timestamp('expire_time')->nullable()->comment('订单过期时间');
            $table->timestamps();

            $table->index(['member_id', 'status'], 'idx_member_status');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['pay_status', 'pay_time'], 'idx_pay_status_time');
            $table->index(['shipping_status'], 'idx_shipping_status');
            $table->index(['order_no'], 'idx_order_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
}
