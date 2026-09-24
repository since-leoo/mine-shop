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

class CreateAfterSaleTables extends Migration
{
    public function up(): void
    {
        Schema::create('order_trade_after_sale', static function (Blueprint $table) {
            $table->comment('售后单表');
            $table->id();
            $table->string('after_sale_no', 32)->unique()->comment('售后单号');
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('order_item_id')->comment('订单商品项ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->enum('type', ['refund_only', 'return_refund', 'exchange'])->comment('售后类型');
            $table->enum('status', ['pending_review', 'waiting_buyer_return', 'waiting_seller_receive', 'waiting_refund', 'refunding', 'waiting_reship', 'reshipped', 'completed', 'closed'])->default('pending_review')->comment('售后状态');
            $table->enum('refund_status', ['none', 'pending', 'processing', 'refunded'])->default('pending')->comment('退款状态');
            $table->enum('return_status', ['not_required', 'pending', 'buyer_shipped', 'seller_received', 'seller_reshipped', 'buyer_received'])->default('not_required')->comment('退货/换货物流状态');
            $table->unsignedInteger('apply_amount')->default(0)->comment('申请金额，单位分');
            $table->unsignedInteger('refund_amount')->default(0)->comment('审核退款金额，单位分');
            $table->unsignedInteger('quantity')->default(1)->comment('售后数量');
            $table->string('reason', 255)->comment('申请原因');
            $table->text('description')->nullable()->comment('问题描述');
            $table->json('images')->nullable()->comment('凭证图片');
            $table->string('buyer_return_logistics_company', 64)->nullable()->comment('买家退货物流公司');
            $table->string('buyer_return_logistics_no', 64)->nullable()->comment('买家退货物流单号');
            $table->string('reship_logistics_company', 64)->nullable()->comment('商家补发物流公司');
            $table->string('reship_logistics_no', 64)->nullable()->comment('商家补发物流单号');
            $table->timestamps();

            $table->index(['order_id'], 'idx_trade_after_sale_order_id');
            $table->index(['order_item_id'], 'idx_trade_after_sale_order_item_id');
            $table->index(['member_id', 'status'], 'idx_trade_after_sale_member_status');
            $table->index(['type', 'status'], 'idx_trade_after_sale_type_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_trade_after_sale');
    }
}
