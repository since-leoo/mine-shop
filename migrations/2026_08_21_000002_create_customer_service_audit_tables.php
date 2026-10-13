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
            $table->comment('客服会话人工接待及转接记录');
            $table->id()->comment('接待记录主键');
            $table->unsignedBigInteger('conversation_id')->comment('所属客服会话ID');
            $table->unsignedBigInteger('agent_id')->comment('参与接待的人工坐席ID');
            $table->string('action', 30)->comment('接待动作：assigned/accepted/transferred_in等');
            $table->unsignedBigInteger('operator_admin_user_id')->nullable()->comment('执行操作的后台用户ID，自动分配为空');
            $table->string('reason', 255)->nullable()->comment('分配或转接原因');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->index(['conversation_id', 'id']);
            $table->index(['agent_id', 'action']);
        });

        Schema::create('customer_service_conversation_logs', static function (Blueprint $table): void {
            $table->comment('客服会话状态与路由操作日志');
            $table->id()->comment('会话操作日志主键');
            $table->unsignedBigInteger('conversation_id')->comment('所属客服会话ID');
            $table->string('action', 30)->comment('会话操作代码');
            $table->string('operator_type', 20)->comment('操作主体类型：member/agent/system');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作主体ID，系统操作可为空');
            $table->json('detail_json')->nullable()->comment('操作原因及路由决策详情');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->index(['conversation_id', 'id']);
        });

        Schema::create('customer_service_ai_runs', static function (Blueprint $table): void {
            $table->comment('客服AI异步任务、预算及执行审计');
            $table->id()->comment('AI运行任务主键');
            $table->unsignedBigInteger('conversation_id')->comment('所属客服会话ID');
            $table->unsignedBigInteger('trigger_message_id')->comment('触发该任务的会员消息ID');
            $table->unsignedInteger('generation')->comment('入队时会话代次，用于拒绝过期回复');
            $table->unsignedInteger('ai_turn')->comment('入队时AI接待轮次');
            $table->unsignedBigInteger('agent_id')->comment('执行任务的AI Agent ID，引用配置快照');
            $table->unsignedBigInteger('config_revision')->comment('任务采用的不可变配置版本ID');
            $table->string('status', 24)->default('pending')->index()->comment('任务状态：pending/processing/completed/cancelled/handed_off');
            $table->string('reason', 100)->nullable()->comment('取消、转人工或执行结果原因');
            $table->unsignedInteger('attempts')->default(0)->comment('任务领取次数');
            $table->unsignedInteger('tokens')->default(0)->comment('已记账Token用量，缺usage时保守估算');
            $table->unsignedInteger('reserved_tokens')->default(0)->comment('执行前预占的Token预算');
            $table->unsignedInteger('latency_ms')->default(0)->comment('任务执行耗时，单位毫秒');
            $table->timestamp('claimed_at')->nullable()->comment('任务领取时间，用于超时回收与每日预算');
            $table->timestamp('created_at')->nullable()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->comment('更新时间');
            $table->unique(['conversation_id', 'trigger_message_id'], 'cs_ai_trigger_unique');
        });

        Schema::create('customer_service_outbox_events', static function (Blueprint $table): void {
            $table->comment('客服事务消息通知及可靠投递记录');
            $table->id()->comment('待投递事件主键');
            $table->string('event_id', 40)->unique()->comment('全局事件编号，用于重复投递去重');
            $table->string('event', 80)->comment('通知事件名称');
            $table->json('payload_json')->comment('待投递的事件内容');
            $table->string('principal_type', 20)->nullable()->comment('目标主体类型：member/agent');
            $table->unsignedBigInteger('principal_id')->nullable()->comment('目标主体ID，坐席列表刷新可为空');
            $table->json('context_json')->comment('事件来源及投递上下文');
            $table->unsignedInteger('attempt_count')->default(0)->comment('累计投递尝试次数');
            $table->timestamp('next_retry_at')->nullable()->comment('投递租约到期或下一次重试时间');
            $table->timestamp('delivered_at')->nullable()->index()->comment('成功投递时间，空表示待处理');
            $table->timestamp('created_at')->comment('事件创建时间');
        });

        Schema::create('customer_service_satisfaction_ratings', static function (Blueprint $table): void {
            $table->comment('客服会话结束后的会员满意度评价');
            $table->id()->comment('满意度评价主键');
            $table->unsignedBigInteger('conversation_id')->comment('被评价的客服会话ID');
            $table->unsignedBigInteger('member_id')->comment('提交评价的会员ID');
            $table->unsignedTinyInteger('score')->comment('满意度评分，范围1至5');
            $table->string('comment', 500)->nullable()->comment('会员评价内容');
            $table->timestamp('created_at')->comment('评价提交时间');
            $table->unique(['conversation_id', 'member_id'], 'cs_rating_member_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_satisfaction_ratings');
        Schema::dropIfExists('customer_service_outbox_events');
        Schema::dropIfExists('customer_service_ai_runs');
        Schema::dropIfExists('customer_service_conversation_logs');
        Schema::dropIfExists('customer_service_conversation_agents');
    }
};
