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

class CreateMallOrderPackagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_packages', static function (Blueprint $table) {
            $table->comment('订单包裹表');
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('package_no', 32)->unique()->comment('包裹号');
            $table->string('express_company', 100)->nullable()->comment('快递公司');
            $table->string('express_no', 100)->nullable()->comment('快递单号');
            $table->enum('status', ['pending', 'shipped', 'delivered'])->default('pending')->comment('包裹状态');
            $table->decimal('weight', 8, 3)->default(0)->comment('包裹重量(kg)');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamp('shipped_at')->nullable()->comment('发货时间');
            $table->timestamp('delivered_at')->nullable()->comment('签收时间');
            $table->timestamps();

            $table->index(['order_id'], 'idx_order_id');
            $table->index(['package_no'], 'idx_package_no');
            $table->index(['express_no'], 'idx_express_no');
            $table->index(['status'], 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_packages');
    }
}
