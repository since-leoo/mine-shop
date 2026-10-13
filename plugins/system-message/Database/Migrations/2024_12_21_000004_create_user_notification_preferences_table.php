<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/** Fresh installations create the current preference model directly, with one row per user. */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_notification_preferences', static function (Blueprint $table): void {
            $table->comment('用户通知偏好表');
            $table->bigIncrements('id')->comment('主键ID');
            $table->unsignedBigInteger('user_id')->comment('后台用户ID');
            $table->json('channel_preferences')->nullable()->comment('通知渠道偏好，键为渠道，值为是否启用');
            $table->json('type_preferences')->nullable()->comment('消息类型偏好，键为类型，值为是否启用');
            $table->boolean('do_not_disturb_enabled')->default(false)->comment('是否启用免打扰');
            $table->time('do_not_disturb_start')->nullable()->comment('免打扰开始时间');
            $table->time('do_not_disturb_end')->nullable()->comment('免打扰结束时间');
            $table->unsignedTinyInteger('min_priority')->default(1)->comment('接收消息的最低优先级：1至5');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->unique('user_id', 'uk_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
