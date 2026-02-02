<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallOrderStatusHistoryTable extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('from_status', 30)->comment('原状态');
            $table->string('to_status', 30)->comment('新状态');
            $table->enum('operator_type', ['system', 'admin', 'member', 'api'])->default('system')->comment('操作员类型');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作员ID');
            $table->string('operator_name', 50)->nullable()->comment('操作员名称');
            $table->string('reason', 255)->nullable()->comment('变更原因');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->json('extra_data')->nullable()->comment('额外数据');
            $table->timestamp('created_at')->nullable()->comment('创建时间');

            $table->index('order_id');
            $table->index(['order_id', 'from_status', 'to_status']);

            $table->comment('订单状态变更历史表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
}
