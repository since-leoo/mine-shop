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

class CreateMallMembersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('members', static function (Blueprint $table) {
            $table->comment('会员表');
            $table->id();
            $table->string('openid', 100)->unique()->comment('微信OpenID');
            $table->string('unionid', 100)->nullable()->comment('微信UnionID');
            $table->string('nickname', 100)->nullable()->comment('昵称');
            $table->string('avatar', 255)->nullable()->comment('头像');
            $table->enum('gender', ['unknown', 'male', 'female'])->default('unknown')->comment('性别');
            $table->string('phone', 20)->nullable()->comment('手机号');
            $table->date('birthday')->nullable()->comment('生日');
            $table->string('city', 50)->nullable()->comment('城市');
            $table->string('province', 50)->nullable()->comment('省份');
            $table->string('country', 50)->nullable()->comment('国家');
            $table->enum('level', ['bronze', 'silver', 'gold', 'diamond'])->default('bronze')->comment('会员等级');
            $table->unsignedInteger('growth_value')->default(0)->comment('成长值');
            $table->unsignedInteger('total_orders')->default(0)->comment('总订单数');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('总消费金额');
            $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
            $table->string('last_login_ip', 45)->nullable()->comment('最后登录IP');
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active')->comment('状态');
            $table->timestamps();

            $table->index(['status', 'level'], 'idx_status_level');
            $table->index(['phone'], 'idx_phone');
            $table->index(['openid'], 'idx_openid');
            $table->index(['unionid'], 'idx_unionid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
}
