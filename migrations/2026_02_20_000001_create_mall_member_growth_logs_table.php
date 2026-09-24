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

class CreateMallMemberGrowthLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_growth_logs', static function (Blueprint $table) {
            $table->comment('会员成长值变动日志表');
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->unsignedInteger('before_value')->default(0)->comment('变动前成长值');
            $table->unsignedInteger('after_value')->default(0)->comment('变动后成长值');
            $table->integer('change_amount')->default(0)->comment('变动值（正为增加，负为减少）');
            $table->string('source', 50)->comment('来源（order_payment/order_refund/sign_in/admin_adjust）');
            $table->string('related_type', 50)->nullable()->comment('关联类型（如 order）');
            $table->unsignedBigInteger('related_id')->nullable()->comment('关联ID');
            $table->string('remark', 255)->nullable()->comment('备注');
            $table->timestamp('created_at')->nullable()->comment('创建时间');

            $table->index(['member_id'], 'idx_member_id');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_growth_logs');
    }
}
