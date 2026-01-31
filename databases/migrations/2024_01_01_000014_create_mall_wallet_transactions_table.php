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

class CreateMallWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mall_wallet_transactions', static function (Blueprint $table) {
            $table->comment('钱包交易记录表');
            $table->id();
            $table->unsignedBigInteger('wallet_id')->nullable()->comment('钱包ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->enum('wallet_type', ['balance', 'points'])->default('balance')->comment('钱包类型');
            $table->string('transaction_no', 32)->unique()->comment('交易号');
            $table->enum('type', ['recharge', 'consume', 'refund', 'withdraw', 'freeze', 'unfreeze', 'adjust_in', 'adjust_out'])->comment('交易类型');
            $table->decimal('amount', 10, 2)->comment('交易金额，正值');
            $table->decimal('balance_before', 10, 2)->comment('交易前余额');
            $table->decimal('balance_after', 10, 2)->comment('交易后余额');
            $table->string('source', 50)->nullable()->comment('来源');
            $table->string('related_type', 50)->nullable()->comment('关联类型');
            $table->unsignedBigInteger('related_id')->nullable()->comment('关联ID');
            $table->string('description', 255)->nullable()->comment('交易描述');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->string('operator_type', 30)->default('admin')->comment('操作人类型');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name', 50)->nullable()->comment('操作人名称');
            $table->timestamps();

            $table->index(['wallet_id'], 'idx_wallet_id');
            $table->index(['member_id'], 'idx_wallet_member');
            $table->index(['wallet_type'], 'idx_wallet_type');
            $table->index(['transaction_no'], 'idx_transaction_no');
            $table->index(['type'], 'idx_type');
            $table->index(['related_type', 'related_id'], 'idx_related');
            $table->index(['source'], 'idx_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mall_wallet_transactions');
    }
}
