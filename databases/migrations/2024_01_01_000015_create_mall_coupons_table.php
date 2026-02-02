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

class CreateMallCouponsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', static function (Blueprint $table) {
            $table->comment('优惠券表');
            $table->id();
            $table->string('name', 100)->comment('优惠券名称');
            $table->enum('type', ['fixed', 'percent'])->comment('优惠券类型');
            $table->decimal('value', 10, 2)->comment('优惠值');
            $table->decimal('min_amount', 10, 2)->default(0)->comment('最低使用金额');
            $table->unsignedInteger('total_quantity')->comment('发放总数');
            $table->unsignedInteger('used_quantity')->default(0)->comment('已使用数量');
            $table->unsignedInteger('per_user_limit')->default(1)->comment('每人限领数量');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();

            $table->index(['status', 'start_time', 'end_time'], 'idx_status_time');
            $table->index(['type'], 'idx_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
}
