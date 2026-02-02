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

class AddRegionFieldsToMembers extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        Schema::table('members', static function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'district')) {
                $table->string('district', 80)->nullable()->after('city')->comment('区县');
            }

            if (! Schema::hasColumn('members', 'street')) {
                $table->string('street', 120)->nullable()->after('district')->comment('街道/乡镇');
            }

            if (! Schema::hasColumn('members', 'region_path')) {
                $table->string('region_path', 255)->nullable()->after('street')->comment('地区编码路径');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        Schema::table('members', static function (Blueprint $table) {
            if (Schema::hasColumn('members', 'region_path')) {
                $table->dropColumn('region_path');
            }
            if (Schema::hasColumn('members', 'street')) {
                $table->dropColumn('street');
            }
            if (Schema::hasColumn('members', 'district')) {
                $table->dropColumn('district');
            }
        });
    }
}

