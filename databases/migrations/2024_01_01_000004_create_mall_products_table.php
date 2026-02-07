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

class CreateMallProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', static function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 50)->unique()->comment('商品编码');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->unsignedBigInteger('brand_id')->nullable()->comment('品牌ID');
            $table->string('name', 200)->comment('商品名称');
            $table->string('sub_title', 255)->nullable()->comment('商品副标题');
            $table->string('main_image', 255)->nullable()->comment('主图');
            $table->json('gallery_images')->nullable()->comment('商品图片集');
            $table->text('description')->nullable()->comment('商品简介');
            $table->longText('detail_content')->nullable()->comment('商品详情');
            $table->json('attributes')->nullable()->comment('商品属性');
            $table->unsignedInteger('min_price')->default(0)->comment('最低价格(分)');
            $table->unsignedInteger('max_price')->default(0)->comment('最高价格(分)');
            $table->unsignedInteger('virtual_sales')->default(0)->comment('虚拟销量');
            $table->unsignedInteger('real_sales')->default(0)->comment('真实销量');
            $table->boolean('is_recommend')->default(false)->comment('是否推荐');
            $table->boolean('is_hot')->default(false)->comment('是否热销');
            $table->boolean('is_new')->default(false)->comment('是否新品');
            $table->unsignedBigInteger('shipping_template_id')->nullable()->comment('运费模板ID');
            $table->unsignedInteger('sort')->default(0)->comment('排序');
            $table->enum('status', ['draft', 'active', 'inactive', 'sold_out'])->default('draft')->comment('状态');
            $table->enum('freight_type', ['default', 'free', 'flat', 'template'])->default('default')->after('shipping_template_id')->comment('运费类型');
            $table->unsignedInteger('flat_freight_amount')->default(0)->after('freight_type')->comment('统一运费金额(分)');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index(['status', 'sort']);
            $table->index(['is_recommend', 'status']);
            $table->index(['is_hot', 'status']);
            $table->index(['is_new', 'status']);

            $table->comment('商品SPU表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}
