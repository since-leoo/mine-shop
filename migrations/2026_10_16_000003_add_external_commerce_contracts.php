<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table): void {
            $table->string('fulfillment_provider', 48)->default('merchant')->index()->comment('通用履约能力标识，merchant为本商城履约');
        });
        Schema::table('order_items', static function (Blueprint $table): void {
            $table->string('fulfillment_provider', 48)->default('merchant')->index()->comment('创建订单时冻结的履约能力标识');
            $table->json('fulfillment_snapshot')->nullable()->comment('创建订单时冻结的外部履约业务快照，不含访问凭证');
        });
        Schema::table('order_packages', static function (Blueprint $table): void {
            $table->string('fulfillment_provider', 48)->default('merchant')->comment('生成包裹的履约能力标识');
            $table->string('integration_key', 64)->nullable()->comment('外部包裹幂等键的SHA256摘要');
            $table->char('integration_digest', 64)->nullable()->comment('包裹物流和明细数量规范化摘要，拒绝同键异参');
            $table->unique(['order_id', 'fulfillment_provider', 'integration_key'], 'uq_package_integration');
        });
        Schema::create('commerce_stock_projections', static function (Blueprint $table): void {
            $table->comment('核心外部库存投影版本与恢复保护记录');
            $table->unsignedBigInteger('sku_id')->primary()->comment('本地商品SKU主键');
            $table->string('provider', 48)->comment('库存所属履约能力标识');
            $table->unsignedBigInteger('version')->default(0)->comment('最后接受的上游库存快照版本');
            $table->unsignedInteger('upstream_stock')->default(0)->comment('上游原始库存快照数量');
            $table->timestamp('created_at')->nullable()->comment('首次建立投影时间');
            $table->timestamp('updated_at')->nullable()->comment('最后应用快照时间');
        });
        Schema::create('commerce_product_imports', static function (Blueprint $table): void {
            $table->comment('核心商品导入幂等记录，隔离外部协议与商品身份');
            $table->bigIncrements('id')->comment('导入记录主键');
            $table->string('provider', 48)->comment('调用方履约能力标识');
            $table->char('import_key', 64)->comment('调用方业务幂等键SHA256摘要');
            $table->unsignedBigInteger('product_id')->nullable()->comment('导入成功后的本地商品ID');
            $table->timestamp('created_at')->nullable()->comment('导入记录创建时间');
            $table->timestamp('updated_at')->nullable()->comment('导入记录最后更新时间');
            $table->unique(['provider', 'import_key'], 'uq_commerce_product_import');
            $table->index('product_id', 'idx_commerce_import_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_stock_projections');
        Schema::dropIfExists('commerce_product_imports');
        Schema::table('order_packages', static function (Blueprint $table): void {
            $table->dropUnique('uq_package_integration');
            $table->dropColumn(['fulfillment_provider', 'integration_key', 'integration_digest']);
        });
        Schema::table('order_items', static function (Blueprint $table): void {
            $table->dropColumn(['fulfillment_provider', 'fulfillment_snapshot']);
        });
        Schema::table('products', static fn (Blueprint $table) => $table->dropColumn('fulfillment_provider'));
    }
};
