<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/**
 * 仪表盘统计表 — 通过定时任务定期聚合写入，Dashboard 查询直接读表.
 */
class CreateDashboardStatisticsTables extends Migration
{
    public function up(): void
    {
        // ── 1. 每日销售统计 ──
        Schema::create('stat_daily_sales', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->unsignedInteger('order_count')->default(0)->comment('订单数');
            $table->unsignedInteger('paid_order_count')->default(0)->comment('已支付订单数');
            $table->unsignedBigInteger('order_amount')->default(0)->comment('订单总额(分)');
            $table->unsignedBigInteger('paid_amount')->default(0)->comment('实付总额(分)');
            $table->unsignedBigInteger('refund_amount')->default(0)->comment('退款总额(分)');
            $table->unsignedInteger('refund_count')->default(0)->comment('退款订单数');
            $table->unsignedBigInteger('shipping_fee_total')->default(0)->comment('运费总额(分)');
            $table->unsignedBigInteger('discount_total')->default(0)->comment('优惠总额(分)');
            $table->unsignedBigInteger('coupon_total')->default(0)->comment('优惠券抵扣总额(分)');
            $table->unsignedInteger('avg_order_amount')->default(0)->comment('平均客单价(分)');
            $table->timestamps();
            $table->unique('date');
            $table->comment('每日销售统计');
        });

        // ── 2. 每日会员统计 ──
        Schema::create('stat_daily_members', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->unsignedInteger('new_members')->default(0)->comment('新增会员数');
            $table->unsignedInteger('active_members')->default(0)->comment('活跃会员数(当日登录)');
            $table->unsignedInteger('total_members')->default(0)->comment('累计会员数');
            $table->unsignedInteger('paying_members')->default(0)->comment('当日付费会员数');
            $table->timestamps();
            $table->unique('date');
            $table->comment('每日会员统计');
        });

        // ── 3. 每日商品统计 ──
        Schema::create('stat_daily_products', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('product_name', 200)->comment('商品名称');
            $table->unsignedInteger('sales_count')->default(0)->comment('销量');
            $table->unsignedBigInteger('sales_amount')->default(0)->comment('销售额(分)');
            $table->unsignedInteger('view_count')->default(0)->comment('浏览量(预留)');
            $table->timestamps();
            $table->unique(['date', 'product_id']);
            $table->index(['date', 'sales_count']);
            $table->comment('每日商品销售统计');
        });

        // ── 4. 每日支付方式统计 ──
        Schema::create('stat_daily_payments', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->string('payment_method', 30)->comment('支付方式');
            $table->unsignedInteger('pay_count')->default(0)->comment('支付笔数');
            $table->unsignedBigInteger('pay_amount')->default(0)->comment('支付金额(分)');
            $table->unsignedInteger('refund_count')->default(0)->comment('退款笔数');
            $table->unsignedBigInteger('refund_amount')->default(0)->comment('退款金额(分)');
            $table->timestamps();
            $table->unique(['date', 'payment_method']);
            $table->comment('每日支付方式统计');
        });

        // ── 5. 每日订单类型统计 ──
        Schema::create('stat_daily_order_types', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->string('order_type', 30)->comment('订单类型');
            $table->unsignedInteger('order_count')->default(0)->comment('订单数');
            $table->unsignedBigInteger('order_amount')->default(0)->comment('订单金额(分)');
            $table->unsignedInteger('paid_count')->default(0)->comment('已支付数');
            $table->unsignedBigInteger('paid_amount')->default(0)->comment('已支付金额(分)');
            $table->timestamps();
            $table->unique(['date', 'order_type']);
            $table->comment('每日订单类型统计');
        });

        // ── 6. 每日分类销售统计 ──
        Schema::create('stat_daily_categories', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->string('category_name', 100)->comment('分类名称');
            $table->unsignedInteger('sales_count')->default(0)->comment('销量');
            $table->unsignedBigInteger('sales_amount')->default(0)->comment('销售额(分)');
            $table->timestamps();
            $table->unique(['date', 'category_id']);
            $table->comment('每日分类销售统计');
        });

        // ── 7. 每日会员等级分布快照 ──
        Schema::create('stat_daily_member_levels', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->string('level', 30)->comment('会员等级');
            $table->unsignedInteger('member_count')->default(0)->comment('会员数');
            $table->timestamps();
            $table->unique(['date', 'level']);
            $table->comment('每日会员等级分布');
        });

        // ── 8. 每日地区销售统计 ──
        Schema::create('stat_daily_regions', static function (Blueprint $table) {
            $table->id();
            $table->date('date')->comment('统计日期');
            $table->string('province', 50)->comment('省份');
            $table->unsignedInteger('order_count')->default(0)->comment('订单数');
            $table->unsignedBigInteger('order_amount')->default(0)->comment('订单金额(分)');
            $table->timestamps();
            $table->unique(['date', 'province']);
            $table->comment('每日地区销售统计');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stat_daily_regions');
        Schema::dropIfExists('stat_daily_member_levels');
        Schema::dropIfExists('stat_daily_categories');
        Schema::dropIfExists('stat_daily_order_types');
        Schema::dropIfExists('stat_daily_payments');
        Schema::dropIfExists('stat_daily_products');
        Schema::dropIfExists('stat_daily_members');
        Schema::dropIfExists('stat_daily_sales');
    }
}
