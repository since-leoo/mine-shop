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

class CreateMallMemberTagRelationsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('member_tag_relations')) {
            return;
        }

        Schema::create('member_tag_relations', static function (Blueprint $table) {
            $table->comment('会员标签关联表');
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->unsignedBigInteger('tag_id')->comment('标签ID');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name', 50)->nullable()->comment('操作人姓名');
            $table->timestamps();

            $table->unique(['member_id', 'tag_id'], 'uniq_member_tag');
            $table->index('tag_id');
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tag_relations');
    }
}
