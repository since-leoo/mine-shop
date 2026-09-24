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

class CreateMallSeckillActivitiesTable extends Migration
{
    public function up(): void
    {
        Schema::create('seckill_activities', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 200)->comment('活动标题');
            $table->text('description')->nullable()->comment('活动描述');
            $table->enum('status', ['pending', 'active', 'ended', 'cancelled'])->default('pending')->comment('状态');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->json('rules')->nullable()->comment('活动规则');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index(['status', 'is_enabled']);
            $table->comment('秒杀活动表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seckill_activities');
    }
}
