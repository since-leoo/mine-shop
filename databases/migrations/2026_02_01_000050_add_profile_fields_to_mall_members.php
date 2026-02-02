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

class AddProfileFieldsToMallMembers extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        Schema::table('members', static function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'source')) {
                $table->string('source', 50)
                    ->default('wechat')
                    ->comment('注册来源：wechat,mini_program,h5,admin')
                    ->after('status');
            }
            if (! Schema::hasColumn('members', 'remark')) {
                $table->string('remark', 255)
                    ->nullable()
                    ->comment('管理员备注')
                    ->after('source');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('members')) {
            return;
        }

        Schema::table('members', static function (Blueprint $table) {
            if (Schema::hasColumn('members', 'remark')) {
                $table->dropColumn('remark');
            }
            if (Schema::hasColumn('members', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
}
