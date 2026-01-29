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

class AddSoftDeletesToMallMembers extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('mall_members') && !Schema::hasColumn('mall_members', 'deleted_at')) {
            Schema::table('mall_members', static function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mall_members') && Schema::hasColumn('mall_members', 'deleted_at')) {
            Schema::table('mall_members', static function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
