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

class CreateMallMemberTagsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_tags')) {
            return;
        }

        Schema::create('member_tags', static function (Blueprint $table) {
            $table->comment('会员标签表');
            $table->id();
            $table->string('name', 50)->comment('标签名称');
            $table->string('color', 20)->nullable()->comment('显示颜色');
            $table->string('description', 255)->nullable()->comment('标签说明');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->timestamps();

            $table->unique('name');
            $table->index('status');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tags');
    }
}
