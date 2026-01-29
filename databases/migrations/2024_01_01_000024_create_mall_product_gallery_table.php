<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallProductGalleryTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_product_gallery', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('image_url', 500)->comment('图片URL');
            $table->string('alt_text', 255)->nullable()->comment('图片描述');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_primary')->default(false)->comment('是否主图');
            $table->timestamps();

            $table->index('product_id');
            $table->index(['product_id', 'is_primary']);
            $table->index(['product_id', 'sort_order']);

            $table->comment('商品图片表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_product_gallery');
    }
}
