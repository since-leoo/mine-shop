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
use Hyperf\DbConnection\Db;

class AddSmsTemplateType extends Migration
{
    public function up(): void
    {
        if (Db::connection()->getDriverName() === 'mysql') {
            Db::statement("ALTER TABLE message_templates MODIFY type ENUM('system','announcement','alert','reminder','sms') NOT NULL DEFAULT 'system'");
        }
    }

    public function down(): void
    {
        if (Db::connection()->getDriverName() === 'mysql') {
            Db::statement("ALTER TABLE message_templates MODIFY type ENUM('system','announcement','alert','reminder') NOT NULL DEFAULT 'system'");
        }
    }
}
