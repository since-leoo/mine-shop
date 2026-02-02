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
        if (Schema::hasTable('brands') && !Schema::hasColumn('brands', 'deleted_at')) {
            Schema::table('brands', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 分类表添加软删除
        if (Schema::hasTable('categories') && !Schema::hasColumn('categories', 'deleted_at')) {
            Schema::table('categories', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // 商品表添加软删除
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'deleted_at')) {
            Schema::table('brands', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'deleted_at')) {
            Schema::table('categories', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
