<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallPaymentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('order_payments', static function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 50)->unique()->comment('支付单号');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('order_no', 50)->comment('订单号');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->string('payment_method', 30)->comment('支付方式');
            $table->unsignedInteger('payment_amount')->comment('应付金额(分)');
            $table->unsignedInteger('paid_amount')->default(0)->comment('实付金额(分)');
            $table->unsignedInteger('refund_amount')->default(0)->comment('已退款金额(分)');
            $table->string('currency', 10)->default('CNY')->comment('货币类型');
            $table->string('status', 30)->default('pending')->comment('支付状态');
            $table->string('third_party_no', 100)->nullable()->comment('第三方支付单号');
            $table->json('third_party_response')->nullable()->comment('第三方响应数据');
            $table->json('callback_data')->nullable()->comment('回调数据');
            $table->timestamp('paid_at')->nullable()->comment('支付时间');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->json('extra_data')->nullable()->comment('额外数据');
            $table->timestamps();

            $table->index('order_id');
            $table->index('member_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index(['status', 'expired_at']);

            $table->comment('订单支付记录表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
}
