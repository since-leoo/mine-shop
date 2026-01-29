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

class CreateMallWalletsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mall_wallets', static function (Blueprint $table) {
            $table->comment('会员钱包表');
            $table->id();
            $table->unsignedBigInteger('member_id')->unique()->comment('会员ID');
            $table->decimal('balance', 10, 2)->default(0)->comment('余额');
            $table->decimal('frozen_balance', 10, 2)->default(0)->comment('冻结余额');
            $table->decimal('total_recharge', 10, 2)->default(0)->comment('累计充值');
            $table->decimal('total_consume', 10, 2)->default(0)->comment('累计消费');
            $table->string('pay_password', 255)->nullable()->comment('支付密码');
            $table->enum('status', ['active', 'frozen'])->default('active')->comment('状态');
            $table->timestamps();

            $table->index(['member_id'], 'idx_member_id');
            $table->index(['status'], 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mall_wallets');
    }
}
