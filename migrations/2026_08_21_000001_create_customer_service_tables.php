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
        if (! Schema::hasTable('customer_service_agents')) {
            Schema::create('customer_service_agents', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('admin_user_id')->unique();
                $table->string('display_name', 60);
                $table->string('avatar', 500)->nullable();
                $table->string('status', 20)->default('offline');
                $table->unsignedInteger('max_conversations')->default(5);
                $table->unsignedInteger('current_conversations')->default(0);
                $table->timestamp('last_heartbeat_at')->nullable();
                $table->timestamps();
                $table->index('status');
            });
        }

        if (! Schema::hasTable('customer_service_conversations')) {
            Schema::create('customer_service_conversations', static function (Blueprint $table): void {
                $table->id();
                $table->string('conversation_no', 40)->unique();
                $table->unsignedBigInteger('member_id');
                $table->string('status', 20)->default('waiting');
                $table->unsignedBigInteger('assigned_agent_id')->nullable();
                $table->string('source', 20)->default('miniapp');
                $table->string('subject', 120)->nullable();
                $table->unsignedBigInteger('last_message_id')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->string('closed_reason', 255)->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->index(['member_id', 'status']);
                $table->index(['assigned_agent_id', 'status']);
            });
        }

        if (! Schema::hasTable('customer_service_messages')) {
            Schema::create('customer_service_messages', static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('conversation_id');
                $table->string('sender_type', 20);
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->string('client_message_id', 64);
                $table->string('message_type', 20);
                $table->json('content_json');
                $table->unsignedBigInteger('reply_to_message_id')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('recalled_at')->nullable();
                $table->timestamps();
                $table->unique(['conversation_id', 'client_message_id'], 'cs_message_conversation_client_unique');
                $table->index(['conversation_id', 'id']);
            });
        }

        if (! Schema::hasTable('customer_service_faqs')) {
            Schema::create('customer_service_faqs', static function (Blueprint $table): void {
                $table->id();
                $table->string('question', 255);
                $table->text('answer');
                $table->string('category_name', 60)->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
                $table->index(['enabled', 'sort']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_faqs');
        Schema::dropIfExists('customer_service_messages');
        Schema::dropIfExists('customer_service_conversations');
        Schema::dropIfExists('customer_service_agents');
    }
};
