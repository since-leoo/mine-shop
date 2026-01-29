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

class AddSoftDeletesToMallTables extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 品牌表添加软删除
        if (Schema::hasTable('mall_brands') && !Schema::hasColumn('mall_brands', 'deleted_at')) {
            Schema::table('mall_brands', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 分类表添加软删除
        if (Schema::hasTable('mall_categories') && !Schema::hasColumn('mall_categories', 'deleted_at')) {
            Schema::table('mall_categories', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 商品表添加软删除
        if (Schema::hasTable('mall_products') && !Schema::hasColumn('mall_products', 'deleted_at')) {
            Schema::table('mall_products', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mall_brands') && Schema::hasColumn('mall_brands', 'deleted_at')) {
            Schema::table('mall_brands', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('mall_categories') && Schema::hasColumn('mall_categories', 'deleted_at')) {
            Schema::table('mall_categories', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('mall_products') && Schema::hasColumn('mall_products', 'deleted_at')) {
            Schema::table('mall_products', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
