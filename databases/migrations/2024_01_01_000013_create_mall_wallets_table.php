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
        Schema::create('wallets', static function (Blueprint $table) {
            $table->comment('会员钱包表');
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->enum('type', ['balance', 'points'])->default('balance')->comment('钱包类型');
            $table->decimal('balance', 10, 2)->default(0)->comment('余额 / 积分余额');
            $table->decimal('frozen_balance', 10, 2)->default(0)->comment('冻结金额');
            $table->decimal('total_recharge', 10, 2)->default(0)->comment('累计收入');
            $table->decimal('total_consume', 10, 2)->default(0)->comment('累计支出');
            $table->string('pay_password', 255)->nullable()->comment('支付密码');
            $table->enum('status', ['active', 'frozen'])->default('active')->comment('状态');
            $table->timestamps();

            $table->unique(['member_id', 'type'], 'uniq_member_wallet_type');
            $table->index(['member_id'], 'idx_member_id');
            $table->index(['type'], 'idx_wallet_type');
            $table->index(['status'], 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
}
