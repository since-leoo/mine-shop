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
use Hyperf\DbConnection\Db;

class AlterFreightTypeAddDefaultToProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            Db::statement("ALTER TABLE products MODIFY COLUMN freight_type ENUM('free','flat','template','default') DEFAULT 'default' COMMENT '运费类型'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table) {
            Db::statement("ALTER TABLE products MODIFY COLUMN freight_type ENUM('free','flat','template') DEFAULT 'free' COMMENT '运费类型'");
        });
    }
}
