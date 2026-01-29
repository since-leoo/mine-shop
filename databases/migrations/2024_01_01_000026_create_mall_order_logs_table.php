<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallOrderLogsTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_order_logs', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('action', 50)->comment('操作动作');
            $table->string('description', 500)->nullable()->comment('操作描述');
            $table->enum('operator_type', ['system', 'admin', 'member'])->default('system')->comment('操作员类型');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作员ID');
            $table->string('operator_name', 50)->nullable()->comment('操作员名称');
            $table->string('old_status', 30)->nullable()->comment('原状态');
            $table->string('new_status', 30)->nullable()->comment('新状态');
            $table->json('extra_data')->nullable()->comment('额外数据');
            $table->timestamps();

            $table->index('order_id');
            $table->index(['order_id', 'action']);
            $table->index('operator_type');

            $table->comment('订单操作日志表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_order_logs');
    }
}
