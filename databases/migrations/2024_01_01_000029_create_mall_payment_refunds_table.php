<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallPaymentRefundsTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_payment_refunds', static function (Blueprint $table) {
            $table->id();
            $table->string('refund_no', 50)->unique()->comment('退款单号');
            $table->unsignedBigInteger('payment_id')->comment('支付记录ID');
            $table->string('payment_no', 50)->comment('支付单号');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('order_no', 50)->comment('订单号');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->decimal('refund_amount', 10, 2)->comment('退款金额');
            $table->string('refund_reason', 500)->nullable()->comment('退款原因');
            $table->string('status', 30)->default('refunding')->comment('退款状态');
            $table->string('third_party_refund_no', 100)->nullable()->comment('第三方退款单号');
            $table->json('third_party_response')->nullable()->comment('第三方响应数据');
            $table->timestamp('processed_at')->nullable()->comment('处理时间');
            $table->enum('operator_type', ['system', 'admin', 'member', 'api'])->default('system')->comment('操作员类型');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作员ID');
            $table->string('operator_name', 50)->nullable()->comment('操作员名称');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->json('extra_data')->nullable()->comment('额外数据');
            $table->timestamps();

            $table->index('payment_id');
            $table->index('order_id');
            $table->index('member_id');
            $table->index('status');

            $table->comment('支付退款记录表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_payment_refunds');
    }
}
