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

class CreateMallCouponUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mall_coupon_users', static function (Blueprint $table) {
            $table->comment('用户优惠券表');
            $table->id();
            $table->unsignedBigInteger('coupon_id')->comment('优惠券ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->unsignedBigInteger('order_id')->nullable()->comment('使用订单ID');
            $table->enum('status', ['unused', 'used', 'expired'])->default('unused')->comment('状态');
            $table->timestamp('received_at')->comment('领取时间');
            $table->timestamp('used_at')->nullable()->comment('使用时间');
            $table->timestamp('expire_at')->comment('过期时间');
            $table->timestamps();

            $table->index(['member_id', 'status'], 'idx_member_status');
            $table->index(['coupon_id'], 'idx_coupon_id');
            $table->index(['order_id'], 'idx_order_id');
            $table->index(['expire_at'], 'idx_expire_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mall_coupon_users');
    }
}
