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

class AddLevelIdToMallMembers extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mall_members')) {
            return;
        }

        Schema::table('mall_members', static function (Blueprint $table) {
            if (! Schema::hasColumn('mall_members', 'level_id')) {
                $table->unsignedBigInteger('level_id')
                    ->nullable()
                    ->after('level')
                    ->comment('会员等级ID');
                $table->index('level_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('mall_members')) {
            return;
        }

        Schema::table('mall_members', static function (Blueprint $table) {
            if (Schema::hasColumn('mall_members', 'level_id')) {
                $table->dropIndex(['level_id']);
                $table->dropColumn('level_id');
            }
        });
    }
}
