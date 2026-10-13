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
            $table->id()->comment('订单主键');
            $table->string('order_no', 32)->unique()->comment('订单号');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->enum('order_type', ['normal', 'seckill', 'group_buy'])->default('normal')->comment('订单类型');
            $table->enum('status', ['pending', 'paid', 'shipped', 'partial_shipped', 'completed', 'cancelled', 'refunded'])->default('pending')->comment('订单状态');
            $table->unsignedInteger('goods_amount')->comment('商品金额(分)');
            $table->unsignedInteger('shipping_fee')->default(0)->comment('运费(分)');
            $table->unsignedInteger('discount_amount')->default(0)->comment('优惠金额(分)');
            $table->unsignedInteger('total_amount')->comment('订单总金额(分)');
            $table->unsignedInteger('pay_amount')->nullable()->comment('实付金额(分)');
            $table->enum('pay_status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending')->comment('支付状态');
            $table->timestamp('pay_time')->nullable()->comment('支付时间');
            $table->string('pay_no', 64)->nullable()->comment('支付流水号');
            $table->enum('pay_method', ['wechat', 'alipay', 'balance'])->nullable()->comment('支付方式');
            $table->text('buyer_remark')->nullable()->comment('买家备注');
            $table->text('seller_remark')->nullable()->comment('卖家备注');
            $table->enum('shipping_status', ['pending', 'partial_shipped', 'shipped', 'delivered'])->default('pending')->comment('发货状态');
            $table->unsignedInteger('package_count')->default(0)->comment('包裹数量');
            $table->timestamp('expire_time')->nullable()->comment('订单过期时间');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');

            $table->index(['member_id', 'status'], 'idx_member_status');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['pay_status', 'pay_time'], 'idx_pay_status_time');
            $table->index(['shipping_status'], 'idx_shipping_status');
            $table->index(['order_no'], 'idx_order_no');
        });

        // 异步下单的入口记录先持久化，队列或 Redis 中断后仍可按提交号恢复。
        Schema::create('order_submissions', static function (Blueprint $table) {
            $table->comment('订单异步提交及恢复记录表');
            $table->id()->comment('提交记录主键');
            $table->string('trade_no', 50)->unique()->comment('提交单号，与最终订单号一致，幂等标识');
            $table->unsignedBigInteger('member_id')->comment('提交会员ID');
            $table->string('status', 30)->default('pending')->comment('提交状态：pending待预占、reserved已预占、created已建单、failed已失败');
            $table->json('payload')->comment('已校验的订单快照、商品、地址、优惠券及库存键');
            $table->string('last_error', 1000)->nullable()->comment('最近一次失败原因');
            $table->timestamp('created_at')->nullable()->comment('首次提交时间');
            $table->timestamp('updated_at')->nullable()->comment('状态或重试更新时间');
            $table->index(['status', 'updated_at']);
        });

        // 与订单、支付、退款共用数据库事务，防止业务已提交而后续事件丢失。
        Schema::create('trade_outbox', static function (Blueprint $table) {
            $table->comment('交易事件持久化及补偿任务表');
            $table->id()->comment('事件记录主键');
            $table->string('business_key', 120)->unique()->comment('业务幂等键，如paid:订单号或refunded:退款号');
            $table->string('event_type', 60)->comment('事件类型，如order.paid、stock.release、refund.succeeded');
            $table->json('payload')->comment('事件或补偿任务的业务数据');
            $table->unsignedInteger('attempts')->default(0)->comment('事件处理失败次数');
            $table->string('last_error', 1000)->nullable()->comment('最近一次处理失败原因');
            $table->timestamp('processed_at')->nullable()->comment('成功处理时间，空值表示待处理');
            $table->timestamp('created_at')->nullable()->comment('事件创建时间');
            $table->timestamp('updated_at')->nullable()->comment('事件状态或重试更新时间');
            $table->index(['processed_at', 'updated_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_outbox');
        Schema::dropIfExists('order_submissions');
        Schema::dropIfExists('orders');
    }
}
