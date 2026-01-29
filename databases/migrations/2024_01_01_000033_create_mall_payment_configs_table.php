<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mall_payment_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('支付方式名称');
            $table->string('code', 50)->unique()->comment('支付方式代码：alipay,wechat,balance,cod');
            $table->string('icon')->nullable()->comment('支付图标');
            $table->json('config')->nullable()->comment('支付配置参数');
            $table->boolean('is_enabled')->default(false)->comment('是否启用');
            $table->integer('sort')->default(0)->comment('排序');
            $table->string('remark')->nullable()->comment('备注');
            $table->timestamps();
            
            $table->index('code');
            $table->index('is_enabled');
            $table->comment('支付配置表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mall_payment_configs');
    }
};
