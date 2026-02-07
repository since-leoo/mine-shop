<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallProductAttributesTable extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', static function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('商品ID');
            $table->string('attribute_name', 100)->comment('属性名称');
            $table->string('value', 500)->comment('属性值');
            $table->timestamps();

            $table->index('product_id');
            $table->index(['product_id', 'attribute_name']);

            $table->comment('商品属性关联表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
}
