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

class CreateMallGroupBuyOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_buy_orders', static function (Blueprint $table) {
            $table->comment('团购订单表');
            $table->id();
            $table->unsignedBigInteger('group_buy_id')->comment('团购活动ID');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->string('group_no', 32)->comment('团号');
            $table->boolean('is_leader')->default(false)->comment('是否团长');
            $table->unsignedInteger('quantity')->default(1)->comment('购买数量');
            $table->unsignedInteger('original_price')->comment('原价(分)');
            $table->unsignedInteger('group_price')->comment('团购价(分)');
            $table->unsignedInteger('total_amount')->comment('总金额(分)');
            $table->enum('status', [
                'pending', 'grouped', 'paid', 'shipped', 'delivered',
                'completed', 'cancelled', 'failed', 'refunded',
            ])->default('pending')->comment('订单状态');
            $table->timestamp('join_time')->comment('参团时间');
            $table->timestamp('group_time')->nullable()->comment('成团时间');
            $table->timestamp('pay_time')->nullable()->comment('支付时间');
            $table->timestamp('cancel_time')->nullable()->comment('取消时间');
            $table->timestamp('expire_time')->nullable()->comment('过期时间');
            $table->string('share_code', 32)->nullable()->comment('分享码');
            $table->unsignedBigInteger('parent_order_id')->nullable()->comment('父订单ID(团长订单)');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index(['group_buy_id'], 'idx_group_buy_id');
            $table->index(['order_id'], 'idx_order_id');
            $table->index(['member_id'], 'idx_member_id');
            $table->index(['group_no'], 'idx_group_no');
            $table->index(['status'], 'idx_status');
            $table->index(['is_leader'], 'idx_is_leader');
            $table->index(['join_time'], 'idx_join_time');
            $table->index(['expire_time'], 'idx_expire_time');
            $table->index(['share_code'], 'idx_share_code');
            $table->index(['parent_order_id'], 'idx_parent_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_buy_orders');
    }
}
