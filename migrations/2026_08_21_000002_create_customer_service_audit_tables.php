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

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_service_conversation_agents', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('action', 30);
            $table->unsignedBigInteger('operator_admin_user_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
            $table->index(['agent_id', 'action']);
        });

        Schema::create('customer_service_conversation_logs', static function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('action', 30);
            $table->string('operator_type', 20);
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->json('detail_json')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_conversation_logs');
        Schema::dropIfExists('customer_service_conversation_agents');
    }
};
