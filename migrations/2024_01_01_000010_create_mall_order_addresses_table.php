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

class CreateMallOrderAddressesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_addresses', static function (Blueprint $table) {
            $table->comment('订单收货地址表');
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->string('name', 50)->comment('收货人姓名');
            $table->string('phone', 20)->comment('收货人电话');
            $table->string('province', 50)->comment('省份');
            $table->string('city', 50)->comment('城市');
            $table->string('district', 50)->comment('区县');
            $table->string('detail', 255)->comment('详细地址');
            $table->string('full_address', 500)->comment('完整地址');
            $table->timestamps();

            $table->index(['order_id'], 'idx_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
}
