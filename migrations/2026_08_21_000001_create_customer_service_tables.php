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
use Hyperf\DbConnection\Db;

return new class extends Migration {
    public function up(): void
    {
        // 全新部署一次创建完整客服结构，不再依赖后续升级补字段或历史数据回填。
        Schema::create('customer_service_agents', static function (Blueprint $table): void {
            $table->comment('客服人工坐席及接待容量');
            $table->id()->comment('人工坐席主键');
            $table->unsignedBigInteger('admin_user_id')->unique()->comment('关联后台用户ID');
            $table->string('display_name', 60)->comment('坐席展示名称');
            $table->string('avatar', 500)->nullable()->comment('坐席头像地址');
            $table->string('status', 20)->default('offline')->comment('坐席状态：online/busy/away/offline');
            $table->boolean('enabled')->default(true)->comment('是否启用坐席');
            $table->integer('priority')->default(0)->comment('分配优先级，数值越大越优先');
            $table->unsignedInteger('weight')->default(1)->comment('加权分配权重');
            $table->unsignedInteger('max_conversations')->default(5)->comment('最大同时接待数');
            $table->unsignedInteger('current_conversations')->default(0)->comment('当前接待数，包含未接单预占');
            $table->json('schedule_json')->nullable()->comment('坐席班次、时区及例外日期');
            $table->timestamp('last_heartbeat_at')->nullable()->comment('最后在线心跳时间');
            $table->timestamp('last_assigned_at')->nullable()->comment('最后分配时间，用于轮询');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->index('status');
        });

        Schema::create('customer_service_conversations', static function (Blueprint $table): void {
            $table->comment('客服会话及AI和人工接待状态');
            $table->id()->comment('客服会话主键');
            $table->string('conversation_no', 40)->unique()->comment('客服会话业务编号');
            $table->unsignedBigInteger('member_id')->comment('发起会话的会员ID');
            $table->string('status', 20)->default('waiting')->comment('兼容展示状态：waiting/assigned/active/closed');
            $table->string('reception_state', 24)->default('human_waiting')->index()->comment('接待状态：ai_active/human_waiting/human_assigned/human_active/human_deferred/closed');
            $table->unsignedBigInteger('assigned_agent_id')->nullable()->comment('当前预占或接待的人工坐席ID');
            $table->unsignedInteger('generation')->default(1)->comment('会话状态代次，使过期AI结果失效');
            $table->unsignedInteger('ai_turn')->default(0)->comment('当前AI接待轮次');
            $table->unsignedInteger('clarification_count')->default(0)->comment('AI已使用澄清次数');
            $table->unsignedBigInteger('category_id')->nullable()->comment('当前问题类目ID，引用配置快照');
            $table->json('classification_json')->nullable()->comment('问题分类来源、路径及关键词命中详情');
            $table->unsignedBigInteger('ai_agent_id')->nullable()->comment('本会话AI Agent ID，引用配置快照');
            $table->unsignedBigInteger('config_revision')->default(0)->comment('本次接待采用的配置版本ID');
            $table->integer('priority')->default(0)->comment('会话排队优先级');
            $table->string('source', 20)->default('miniapp')->comment('会话来源渠道');
            $table->string('subject', 120)->nullable()->comment('会话主题');
            $table->unsignedBigInteger('last_message_id')->nullable()->comment('会话最后一条消息ID');
            $table->unsignedBigInteger('last_sequence')->default(0)->comment('会话已分配的最大消息序号');
            $table->timestamp('last_message_at')->nullable()->comment('最后消息时间');
            $table->timestamp('queue_entered_at')->nullable()->comment('本次进入人工队列的时间');
            $table->timestamp('assignment_expires_at')->nullable()->comment('人工预占等待接单的截止时间');
            $table->timestamp('deferred_at')->nullable()->comment('转入留言状态的时间');
            $table->string('handoff_reason', 80)->nullable()->comment('转人工或转留言原因代码');
            $table->string('resolution_status', 20)->default('unknown')->comment('问题解决状态');
            $table->string('resolution_source', 30)->nullable()->comment('解决状态的确认来源');
            $table->timestamp('resolved_at')->nullable()->comment('问题确认解决时间');
            $table->unsignedBigInteger('closed_by')->nullable()->comment('关闭会话的操作人ID，类型见关闭原因');
            $table->string('closed_reason', 255)->nullable()->comment('关闭原因或操作人类型代码');
            $table->timestamp('closed_at')->nullable()->comment('会话关闭时间');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->index(['member_id', 'status']);
            $table->index(['assigned_agent_id', 'status']);
        });

        Schema::create('customer_service_messages', static function (Blueprint $table): void {
            $table->comment('客服会话消息及发送幂等记录');
            $table->id()->comment('客服消息主键');
            $table->unsignedBigInteger('conversation_id')->comment('所属客服会话ID');
            $table->string('sender_type', 20)->comment('发送者类型：member/agent/ai/system');
            $table->unsignedBigInteger('sender_id')->nullable()->comment('发送者ID，系统消息可为空');
            $table->string('client_message_id', 64)->comment('发送端幂等标识，同一发送者不可重复');
            $table->unsignedBigInteger('sequence')->comment('会话内递增消息序号，用于断线补拉');
            $table->string('message_type', 20)->comment('消息类型：text/image/product/faq');
            $table->json('content_json')->comment('消息内容快照');
            $table->unsignedBigInteger('reply_to_message_id')->nullable()->comment('被回复的消息ID');
            $table->unsignedBigInteger('ai_run_id')->nullable()->unique()->comment('产生该消息的AI运行ID');
            $table->string('visibility', 16)->default('public')->comment('消息可见范围：public/internal');
            $table->json('evidence_json')->nullable()->comment('AI回答引用的知识证据ID列表');
            $table->timestamp('sent_at')->nullable()->comment('消息发送时间');
            $table->timestamp('recalled_at')->nullable()->comment('消息撤回时间');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            // 不同发送者可以复用客户端编号，但单个发送者的重试只写入一次。
            $table->unique(['conversation_id', 'sender_type', 'sender_id', 'client_message_id'], 'cs_message_sender_client_unique');
            $table->unique(['conversation_id', 'sequence'], 'cs_message_sequence_unique');
            $table->index(['conversation_id', 'id']);
        });

        Schema::create('customer_service_faqs', static function (Blueprint $table): void {
            $table->comment('客服常见问题草稿及发布指针');
            $table->id()->comment('常见问题主键');
            $table->string('question', 255)->comment('问题标题');
            $table->text('answer')->comment('当前草稿答案');
            $table->string('category_name', 60)->nullable()->comment('展示用类目名称');
            $table->unsignedBigInteger('category_id')->nullable()->comment('问题类目ID，引用配置快照');
            $table->unsignedBigInteger('published_revision_id')->nullable()->comment('当前发布的知识版本ID');
            $table->json('alternate_questions')->nullable()->comment('相似问法列表');
            $table->unsignedInteger('sort')->default(0)->comment('展示排序值');
            $table->boolean('enabled')->default(true)->comment('是否允许展示和知识检索');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->index(['enabled', 'sort']);
        });

        Schema::create('customer_service_faq_revisions', static function (Blueprint $table): void {
            $table->comment('客服知识不可变发布版本');
            $table->id()->comment('知识版本主键');
            $table->unsignedBigInteger('faq_id')->comment('所属常见问题ID');
            $table->unsignedInteger('version')->comment('该问题的递增发布版本号');
            $table->json('content_snapshot')->comment('发布时的问题、答案及检索信息快照');
            $table->timestamp('effective_at')->nullable()->comment('开始生效时间，空表示立即生效');
            $table->timestamp('expires_at')->nullable()->comment('停止生效时间，空表示长期有效');
            $table->timestamp('created_at')->comment('版本创建时间');
            $table->unique(['faq_id', 'version'], 'cs_faq_version_unique');
        });

        Schema::create('customer_service_config_revisions', static function (Blueprint $table): void {
            $table->comment('客服配置不可变版本快照');
            $table->id()->comment('配置版本主键');
            $table->json('snapshot_json')->comment('接待设置、类目、关键词、技能及AI定义完整快照');
            $table->boolean('published')->default(false)->comment('本版本是否曾作为发布版本保存');
            $table->unsignedBigInteger('operator_id')->comment('保存配置的后台用户ID');
            $table->timestamp('created_at')->comment('配置版本创建时间');
        });

        Schema::create('customer_service_config_state', static function (Blueprint $table): void {
            $table->comment('客服配置草稿与发布版本指针');
            $table->unsignedBigInteger('id')->primary()->comment('固定单例主键1，用于配置和预算并发锁');
            $table->unsignedBigInteger('draft_revision')->default(0)->comment('当前草稿版本ID，0表示尚未保存');
            $table->unsignedBigInteger('published_revision')->default(0)->comment('当前发布版本ID，0表示采用系统默认配置');
        });
        // 仅初始化配置锁与版本指针，不创建任何历史业务记录。
        Db::table('customer_service_config_state')->insert(['id' => 1, 'draft_revision' => 0, 'published_revision' => 0]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_config_state');
        Schema::dropIfExists('customer_service_config_revisions');
        Schema::dropIfExists('customer_service_faq_revisions');
        Schema::dropIfExists('customer_service_faqs');
        Schema::dropIfExists('customer_service_messages');
        Schema::dropIfExists('customer_service_conversations');
        Schema::dropIfExists('customer_service_agents');
    }
};
