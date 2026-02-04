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

class CreateMallMemberAddressesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_addresses', static function (Blueprint $table) {
            $table->comment('会员收货地址表');
            $table->id();
            $table->unsignedBigInteger('member_id')->comment('会员ID');
            $table->string('name', 50)->comment('收货人姓名');
            $table->string('phone', 20)->comment('收货人电话');
            $table->string('province', 50)->comment('省份');
            $table->string('city', 50)->comment('城市');
            $table->string('district', 50)->comment('区县');
            $table->string('detail', 255)->comment('详细地址');
            $table->string('full_address', 500)->comment('完整地址');
            $table->boolean('is_default')->default(false)->comment('是否默认地址');
            $table->timestamps();

            $table->index(['member_id', 'is_default'], 'idx_member_default');
            $table->index(['member_id'], 'idx_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_addresses');
    }
}
