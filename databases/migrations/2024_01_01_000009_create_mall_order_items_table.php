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

class CreateMallOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', static function (Blueprint $table) {
            $table->comment('订单商品表');
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->string('product_name', 200)->comment('商品名称');
            $table->string('sku_name', 200)->comment('SKU名称');
            $table->string('product_image', 255)->nullable()->comment('商品图片');
            $table->json('spec_values')->nullable()->comment('规格值');
            $table->decimal('unit_price', 10, 2)->comment('单价');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->decimal('total_price', 10, 2)->comment('小计');
            $table->timestamps();

            $table->index(['order_id'], 'idx_order_id');
            $table->index(['product_id'], 'idx_product_id');
            $table->index(['sku_id'], 'idx_sku_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
}
