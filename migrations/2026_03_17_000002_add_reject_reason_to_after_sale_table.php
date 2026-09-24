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

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_trade_after_sale', static function (Blueprint $table) {
            $table->string('reject_reason', 200)->nullable()->comment('??????')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('order_trade_after_sale', static function (Blueprint $table) {
            $table->dropColumn('reject_reason');
        });
    }
};
