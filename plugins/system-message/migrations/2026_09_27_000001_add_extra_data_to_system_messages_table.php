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

class AddExtraDataToSystemMessagesTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('system_messages', 'extra_data')) {
            Schema::table('system_messages', static function (Blueprint $table): void {
                $table->json('extra_data')->nullable()->comment('扩展数据')->after('template_variables');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('system_messages', 'extra_data')) {
            Schema::table('system_messages', static function (Blueprint $table): void {
                $table->dropColumn('extra_data');
            });
        }
    }
}
