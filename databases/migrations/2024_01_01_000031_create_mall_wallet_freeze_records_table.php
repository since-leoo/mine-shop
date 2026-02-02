<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallWalletFreezeRecordsTable extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_freeze_records', static function (Blueprint $table) {
            $table->id();
            $table->string('freeze_no', 50)->unique()->comment('冻结单号');
            $table->unsignedBigInteger('wallet_id')->comment('钱包ID');
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->decimal('freeze_amount', 10, 2)->comment('申请冻结金额');
            $table->decimal('frozen_amount', 10, 2)->default(0)->comment('实际冻结金额');
            $table->decimal('released_amount', 10, 2)->default(0)->comment('已释放金额');
            $table->string('status', 30)->default('frozen')->comment('状态');
            $table->string('freeze_reason', 255)->nullable()->comment('冻结原因');
            $table->string('release_reason', 255)->nullable()->comment('释放原因');
            $table->string('related_type', 30)->nullable()->comment('关联类型');
            $table->unsignedBigInteger('related_id')->nullable()->comment('关联ID');
            $table->string('related_no', 50)->nullable()->comment('关联单号');
            $table->enum('operator_type', ['system', 'admin', 'member', 'api'])->default('system')->comment('操作员类型');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作员ID');
            $table->string('operator_name', 50)->nullable()->comment('操作员名称');
            $table->timestamp('frozen_at')->nullable()->comment('冻结时间');
            $table->timestamp('released_at')->nullable()->comment('释放时间');
            $table->timestamp('expired_at')->nullable()->comment('过期时间');
            $table->string('remark', 500)->nullable()->comment('备注');
            $table->json('extra_data')->nullable()->comment('额外数据');
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('member_id');
            $table->index('status');
            $table->index(['related_type', 'related_id']);
            $table->index(['status', 'expired_at']);

            $table->comment('钱包冻结记录表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_freeze_records');
    }
}
