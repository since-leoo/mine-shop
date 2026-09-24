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

class CreateMallOrderPackageItemsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_package_items', static function (Blueprint $table) {
            $table->comment('订单包裹商品表');
            $table->id();
            $table->unsignedBigInteger('package_id')->comment('包裹ID');
            $table->unsignedBigInteger('order_item_id')->comment('订单商品ID');
            $table->unsignedInteger('quantity')->comment('发货数量');
            $table->timestamps();

            $table->index(['package_id'], 'idx_package_id');
            $table->index(['order_item_id'], 'idx_order_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_package_items');
    }
}
