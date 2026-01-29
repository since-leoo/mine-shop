<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallProductAttributesTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_product_attributes', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->unsignedBigInteger('attribute_id')->comment('属性ID');
            $table->unsignedBigInteger('attribute_value_id')->nullable()->comment('属性值ID');
            $table->string('value', 255)->nullable()->comment('自定义属性值');
            $table->timestamps();

            $table->index('product_id');
            $table->index('attribute_id');
            $table->index(['product_id', 'attribute_id']);

            $table->comment('商品属性关联表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_product_attributes');
    }
}
