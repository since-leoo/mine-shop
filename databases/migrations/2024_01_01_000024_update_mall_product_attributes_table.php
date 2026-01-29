<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class UpdateMallProductAttributesTable extends Migration
{
    public function up(): void
    {
        Schema::table('mall_product_attributes', static function (Blueprint $table) {
            // 删除旧索引
            $table->dropIndex('mall_product_attributes_attribute_id_index');
            $table->dropIndex('mall_product_attributes_product_id_attribute_id_index');
            
            // 删除旧字段
            $table->dropColumn(['attribute_id', 'attribute_value_id']);
            
            // 添加新字段
            $table->string('attribute_name', 100)->after('product_id')->comment('属性名称');
            $table->string('value', 500)->nullable(false)->change()->comment('属性值');
            
            // 添加索引
            $table->index(['product_id', 'attribute_name']);
        });
    }

    public function down(): void
    {
        Schema::table('mall_product_attributes', static function (Blueprint $table) {
            // 删除新索引
            $table->dropIndex('mall_product_attributes_product_id_attribute_name_index');
            
            // 删除新字段
            $table->dropColumn('attribute_name');
            
            // 恢复旧字段
            $table->unsignedBigInteger('attribute_id')->after('product_id')->comment('属性ID');
            $table->unsignedBigInteger('attribute_value_id')->nullable()->after('attribute_id')->comment('属性值ID');
            $table->string('value', 255)->nullable()->change()->comment('自定义属性值');
            
            // 恢复旧索引
            $table->index('attribute_id');
            $table->index(['product_id', 'attribute_id']);
        });
    }
}
