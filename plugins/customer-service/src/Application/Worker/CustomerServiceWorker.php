<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application\Worker;

use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\DbConnection\Db;
use Plugin\CustomerService\Application\CustomerServiceOutbox;
use Plugin\CustomerService\Domain\Contract\CustomerServiceEventPublisherInterface;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Service\DomainAiReceptionService;
use Plugin\CustomerService\Domain\Service\DomainConversationService;
use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceMessage;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

#[Crontab(name: 'customer-service-worker', rule: '*/5 * * * * *', callback: 'execute', memo: '客服消息投递、AI 异步处理及排队补偿', enable: true)]
final class CustomerServiceWorker
{
    public function __construct(private readonly SupportConfigurationInterface $configuration, private readonly CustomerServiceSettingsResolver $settings, private readonly DomainAiReceptionService $ai, private readonly DomainConversationService $domain, private readonly CustomerServiceOutbox $outbox, private readonly CustomerServiceEventPublisherInterface $publisher) {}

    public function execute(): void
    {
        $this->dispatchOutbox();
        $this->maintainQueue();
        $this->processOneAiRun();
        $this->dispatchOutbox();
    }

    public function processOneAiRun(): bool
    {
        // 先用短事务领取任务，再进行外部请求，避免网络等待占用会话锁。
        $run = Db::transaction(function () {
            $run = Db::table('customer_service_ai_runs')->where('status', 'pending')->orderBy('id')->lockForUpdate()->first();
            if (!$run) { return null; }
            Db::table('customer_service_ai_runs')->where('id', $run->id)->update(['status' => 'processing', 'reserved_tokens' => 0, 'claimed_at' => date('Y-m-d H:i:s'), 'attempts' => (int) $run->attempts + 1]);
            return (array) $run;
        });
        if (!$run) { return false; }
        $start = microtime(true);
        $conversation = CustomerServiceConversation::query()->find((int) $run['conversation_id']);
        $enabled = $this->settings->toArray()['enabled'] && $this->settings->toArray()['ai_enabled'];
        if (!$conversation || !$this->domain->allowsAi($conversation, (int) $run['generation'], (int) $run['ai_turn'], $enabled)) {
            Db::table('customer_service_ai_runs')->where('id', $run['id'])->update(['status' => 'cancelled', 'reason' => 'stale_generation', 'reserved_tokens' => 0]);
            return true;
        }
        try {
            $snapshot = $this->configuration->revision((int) $run['config_revision'])['snapshot'];
            $messages = CustomerServiceMessage::query()->where('conversation_id', $conversation->id)->where('visibility', 'public')->where('id', '<=', $run['trigger_message_id'])->latest('id')->limit(20)->get()->reverse()->values()->toArray();
            $prepared = $this->ai->prepare($snapshot, $run, $conversation->toArray(), $messages);
            if ($prepared instanceof AiAnswerVo) { $answer = $prepared; }
            else {
                $input = \Plugin\CustomerService\Domain\ValueObject\AiPromptVo::inputUpperBound($prepared['agent'], $prepared['messages'], $prepared['evidence']);
                $output = $this->reserveBudget((int) $run['id'], $input, min(4096, max(128, (int) ($prepared['agent']['max_output_tokens'] ?? 1024))));
                if ($output === 0) { $answer = new AiAnswerVo('handoff', reason: 'budget_exhausted'); }
                else {
                    $prepared['agent']['max_output_tokens'] = $output;
                    $answer = $this->ai->complete($prepared);
                }
            }
        } catch (\Throwable) {
            // 供应商异常可能包含请求头，审计只记录固定原因码，避免凭据泄漏。
            $answer = new AiAnswerVo('handoff', reason: 'provider_failed');
        }
        $this->finishAiRun($run, $answer, (int) ((microtime(true) - $start) * 1000));
        return true;
    }

    public function reserveBudget(int $runId, int $inputUpperBound, int $requestedOutput): int
    {
        return Db::transaction(function () use ($runId, $inputUpperBound, $requestedOutput) {
            // 全局配置单例兼作预算互斥锁，避免多个 Worker 同时花掉相同额度。
            Db::table('customer_service_config_state')->where('id', 1)->lockForUpdate()->first();
            $run = Db::table('customer_service_ai_runs')->where('id', $runId)->lockForUpdate()->first();
            if (!$run || $run->status !== 'processing') { return 0; }
            if ((int) $run->reserved_tokens > 0) { return max(0, min($requestedOutput, (int) $run->reserved_tokens - $inputUpperBound)); }
            $spent = (int) Db::table('customer_service_ai_runs')->where('claimed_at', '>=', date('Y-m-d 00:00:00'))->sum('tokens');
            $reserved = (int) Db::table('customer_service_ai_runs')->where('status', 'processing')->sum('reserved_tokens');
            $available = (int) $this->settings->toArray()['ai_daily_token_budget'] - $spent - $reserved;
            $output = min($requestedOutput, $available - $inputUpperBound);
            if ($output < 128) { return 0; }
            Db::table('customer_service_ai_runs')->where('id', $runId)->where('status', 'processing')->update(['reserved_tokens' => $inputUpperBound + $output]);
            return $output;
        });
    }

    public function finishAiRun(array $run, AiAnswerVo $answer, int $latency): void
    {
        Db::transaction(function () use ($run, $answer, $latency) {
            $conversation = CustomerServiceConversation::query()->whereKey($run['conversation_id'])->lockForUpdate()->first();
            $stored = Db::table('customer_service_ai_runs')->where('id', $run['id'])->lockForUpdate()->first();
            if (!$stored || $stored->status !== 'processing') { return; }
            if (!$conversation) {
                Db::table('customer_service_ai_runs')->where('id', $run['id'])->update(['status' => 'cancelled', 'reason' => 'conversation_missing', 'tokens' => $answer->tokens > 0 ? $answer->tokens : (int) $stored->reserved_tokens, 'reserved_tokens' => 0]);
                return;
            }
            // 回写前重新检查会话代次和接待状态；转人工之后的迟到答案不再投递。
            $result = $this->domain->applyAiAnswer($conversation, $run, $answer);
            if ($result['changed']) { $this->outbox->conversationChanged($conversation); }
            if ($result['message']) { $this->outbox->messageCreated($conversation, $result['message']->toArray()); }
            Db::table('customer_service_ai_runs')->where('id', $run['id'])->update(['status' => $result['status'], 'reason' => $result['reason'], 'tokens' => $answer->tokens > 0 ? $answer->tokens : (int) $stored->reserved_tokens, 'reserved_tokens' => 0, 'latency_ms' => $latency, 'updated_at' => date('Y-m-d H:i:s')]);
        });
    }

    public function dispatchOutbox(int $limit = 100): int
    {
        $delivered = 0;
        $rows = Db::table('customer_service_outbox_events')->whereNull('delivered_at')->where(function ($q) { $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', date('Y-m-d H:i:s')); })->orderBy('id')->limit($limit)->get();
        foreach ($rows as $row) {
            // 先获取投递租约；通知至少投递一次，由 event_id 和消息ID消除重复。
            $claimed = Db::table('customer_service_outbox_events')->where('id', $row->id)->whereNull('delivered_at')->where(function ($q) { $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', date('Y-m-d H:i:s')); })->update(['next_retry_at' => date('Y-m-d H:i:s', time() + 30), 'attempt_count' => (int) $row->attempt_count + 1]);
            if (!$claimed) { continue; }
            try {
                $this->publisher->publish($row->event, json_decode($row->payload_json, true, 512, JSON_THROW_ON_ERROR), $row->principal_type, $row->principal_id ? (int) $row->principal_id : null, json_decode($row->context_json, true, 512, JSON_THROW_ON_ERROR));
                Db::table('customer_service_outbox_events')->where('id', $row->id)->update(['delivered_at' => date('Y-m-d H:i:s')]);
                ++$delivered;
            } catch (\Throwable) {
                Db::table('customer_service_outbox_events')->where('id', $row->id)->update(['next_retry_at' => date('Y-m-d H:i:s', time() + min(300, 2 ** min(8, (int) $row->attempt_count + 1)))]);
            }
        }
        return $delivered;
    }

    public function maintainQueue(): void
    {
        $now = new \DateTimeImmutable();
        $staleRuns = Db::table('customer_service_ai_runs')->where('status', 'processing')->where('claimed_at', '<', date('Y-m-d H:i:s', time() - 120))->get();
        foreach ($staleRuns as $run) { $this->finishAiRun((array) $run, new AiAnswerVo('handoff', reason: 'worker_timeout'), 120000); }
        $ids = CustomerServiceConversation::query()->whereIn('reception_state', ['human_waiting', 'human_assigned', 'human_deferred', 'ai_active'])->orderByDesc('priority')->orderBy('queue_entered_at')->limit(200)->pluck('id');
        foreach ($ids as $id) {
            Db::transaction(function () use ($id, $now) {
                $conversation = CustomerServiceConversation::query()->whereKey($id)->lockForUpdate()->first();
                if ($conversation && $this->domain->maintainQueue($conversation, $now)) { $this->outbox->conversationChanged($conversation); }
            });
        }
    }
}
