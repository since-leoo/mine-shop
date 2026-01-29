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

class CreateMallSeckillSessionProductsTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_seckill_session_products', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('activity_id')->comment('活动ID');
            $table->unsignedBigInteger('session_id')->comment('场次ID');
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('product_sku_id')->comment('SKU ID');
            $table->decimal('original_price', 10, 2)->comment('原价');
            $table->decimal('seckill_price', 10, 2)->comment('秒杀价');
            $table->unsignedInteger('quantity')->default(0)->comment('库存');
            $table->unsignedInteger('sold_quantity')->default(0)->comment('已售数量');
            $table->unsignedInteger('max_quantity_per_user')->default(1)->comment('单品每人限购');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->timestamps();

            $table->index(['activity_id']);
            $table->index(['session_id']);
            $table->index(['product_id']);
            $table->index(['product_sku_id']);
            $table->comment('秒杀场次商品表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_seckill_session_products');
    }
}
