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

class CreateMallProductSkusTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_skus', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('sku_code', 50)->unique()->comment('SKU编码');
            $table->string('sku_name', 200)->comment('SKU名称');
            $table->json('spec_values')->nullable()->comment('规格值');
            $table->string('image', 255)->nullable()->comment('SKU图片');
            $table->decimal('cost_price', 10, 2)->default(0)->comment('成本价');
            $table->decimal('market_price', 10, 2)->default(0)->comment('市场价');
            $table->decimal('sale_price', 10, 2)->comment('销售价');
            $table->unsignedInteger('stock')->default(0)->comment('库存');
            $table->unsignedInteger('warning_stock')->default(10)->comment('预警库存');
            $table->decimal('weight', 8, 3)->default(0)->comment('重量(kg)');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['status', 'stock']);
            $table->comment('商品SKU表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
}
