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
        Schema::table('members', static function (Blueprint $table) {
            $table->string('password', 255)->nullable()->after('phone')->comment('登录密码');
        });
    }

    public function down(): void
    {
        Schema::table('members', static function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
