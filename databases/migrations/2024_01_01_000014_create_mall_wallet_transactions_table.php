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
            $table->unsignedBigInteger('wallet_id')->comment('钱包ID');
            $table->string('transaction_no', 32)->unique()->comment('交易号');
            $table->enum('type', ['recharge', 'consume', 'refund', 'withdraw', 'freeze', 'unfreeze'])->comment('交易类型');
            $table->decimal('amount', 10, 2)->comment('交易金额');
            $table->decimal('balance_before', 10, 2)->comment('交易前余额');
            $table->decimal('balance_after', 10, 2)->comment('交易后余额');
            $table->string('related_type', 50)->nullable()->comment('关联类型');
            $table->unsignedBigInteger('related_id')->nullable()->comment('关联ID');
            $table->string('description', 255)->comment('交易描述');
            $table->timestamps();

            $table->index(['wallet_id'], 'idx_wallet_id');
            $table->index(['transaction_no'], 'idx_transaction_no');
            $table->index(['type'], 'idx_type');
            $table->index(['related_type', 'related_id'], 'idx_related');
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
