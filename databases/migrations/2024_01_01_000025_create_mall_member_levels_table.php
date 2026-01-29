<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMallMemberLevelsTable extends Migration
{
    public function up(): void
    {
        Schema::create('mall_member_levels', static function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('等级名称');
            $table->unsignedTinyInteger('level')->default(0)->comment('等级值');
            $table->unsignedInteger('growth_value_min')->default(0)->comment('最低成长值');
            $table->unsignedInteger('growth_value_max')->nullable()->comment('最高成长值');
            $table->decimal('discount_rate', 5, 2)->default(100.00)->comment('折扣率(百分比)');
            $table->decimal('point_rate', 5, 2)->default(100.00)->comment('积分倍率(百分比)');
            $table->json('privileges')->nullable()->comment('特权配置');
            $table->string('icon', 255)->nullable()->comment('等级图标');
            $table->string('color', 20)->nullable()->comment('等级颜色');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('状态');
            $table->unsignedInteger('sort_order')->default(0)->comment('排序');
            $table->text('description')->nullable()->comment('等级描述');
            $table->timestamps();

            $table->unique('level');
            $table->index('status');
            $table->index('sort_order');

            $table->comment('会员等级表');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mall_member_levels');
    }
}
