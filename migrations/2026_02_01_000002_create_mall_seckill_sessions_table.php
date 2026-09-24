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

class CreateMallSeckillSessionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('seckill_sessions', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('activity_id')->comment('活动ID');
            $table->timestamp('start_time')->comment('开始时间');
            $table->timestamp('end_time')->comment('结束时间');
            $table->enum('status', ['pending', 'active', 'ended', 'cancelled', 'sold_out'])->default('pending')->comment('状态');
            $table->unsignedInteger('max_quantity_per_user')->default(1)->comment('活动每人限购');
            $table->unsignedInteger('total_quantity')->default(0)->comment('活动总库存');
            $table->unsignedInteger('sold_quantity')->default(0)->comment('活动已售数量');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->boolean('is_enabled')->default(true)->comment('是否启用');
            $table->json('rules')->nullable()->comment('场次规则');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index(['activity_id']);
            $table->index(['start_time', 'end_time']);
            $table->index(['status', 'is_enabled']);
            $table->comment('秒杀场次表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seckill_sessions');
    }
}
