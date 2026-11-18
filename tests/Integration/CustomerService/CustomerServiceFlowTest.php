<?php

declare(strict_types=1);

namespace HyperfTests\Integration\CustomerService;

use Hyperf\DbConnection\Db;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\CustomerService\Application\Admin\AppCustomerServiceCommandService;
use Plugin\CustomerService\Application\Api\AppApiCustomerServiceCommandService;
use Plugin\CustomerService\Application\CustomerServiceOutbox;
use Plugin\CustomerService\Application\Worker\CustomerServiceWorker;
use Plugin\CustomerService\Domain\Contract\AiProviderInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServiceEventPublisherInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServiceKnowledgeInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServicePresenceInterface;
use Plugin\CustomerService\Domain\Mapper\CustomerServiceConversationMapper;
use Plugin\CustomerService\Domain\Repository\CustomerServiceConversationRepository;
use Plugin\CustomerService\Domain\Service\DomainAgentService;
use Plugin\CustomerService\Domain\Service\DomainAiReceptionService;
use Plugin\CustomerService\Domain\Service\DomainCategoryService;
use Plugin\CustomerService\Domain\Service\DomainConfigurationService;
use Plugin\CustomerService\Domain\Service\DomainConversationService;
use Plugin\CustomerService\Domain\Service\DomainKeywordService;
use Plugin\CustomerService\Domain\Service\DomainRoutingService;
use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceAgent;
use Plugin\CustomerService\Infrastructure\Model\CustomerServiceConversation;
use Plugin\CustomerService\Infrastructure\Repository\DatabaseSupportConfiguration;
use Plugin\CustomerService\Service\CustomerServiceAssignmentService;
use Plugin\CustomerService\Service\CustomerServiceSettingsResolver;

final class CustomerServiceFlowTest extends DatabaseTestCase
{
    private DatabaseSupportConfiguration $config;
    private AppApiCustomerServiceCommandService $api;
    private AppCustomerServiceCommandService $admin;
    private CustomerServiceWorker $worker;
    private DomainConfigurationService $configDomain;
    private AiProviderInterface $provider;
    private CustomerServiceKnowledgeInterface $knowledge;
    private CustomerServiceEventPublisherInterface $publisher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection->getSchemaBuilder()->create('members', function ($table) { $table->id(); $table->string('nickname')->default('用户'); });
        Db::table('members')->insert(['id' => 1, 'nickname' => '会员']);
        $this->migrate('migrations/2026_08_21_000001_create_customer_service_tables.php');
        $this->migrate('migrations/2026_08_21_000002_create_customer_service_audit_tables.php');
        $this->config = new DatabaseSupportConfiguration();
        $settings = $this->createMock(CustomerServiceSettingsResolver::class);
        $settings->method('toArray')->willReturnCallback(fn () => array_replace(DomainConfigurationService::DEFAULTS, $this->config->published()['snapshot']['settings'] ?? []));
        $categories = new DomainCategoryService();
        $keywords = new DomainKeywordService($categories);
        $routing = new DomainRoutingService($categories);
        $repository = new CustomerServiceConversationRepository();
        $mapper = new CustomerServiceConversationMapper();
        $presence = $this->createMock(CustomerServicePresenceInterface::class);
        $presence->method('isOnline')->willReturn(true);
        $assignment = new CustomerServiceAssignmentService($settings, $this->config, $routing, $mapper, $presence);
        $domain = new DomainConversationService($repository, $this->config, $settings, $categories, $keywords, $mapper, $assignment, $routing);
        $outbox = new CustomerServiceOutbox();
        $faqs = new \Plugin\CustomerService\Domain\Service\DomainFaqService($this->config, $categories);
        $products = $this->createMock(\App\Domain\Catalog\Product\Contract\ProductSnapshotInterface::class);
        $this->api = new AppApiCustomerServiceCommandService($repository, $settings, $products, $domain, $outbox, $this->createMock(\Plugin\CustomerService\Domain\Contract\CustomerServiceAttachmentInterface::class), $faqs);
        $this->configDomain = new DomainConfigurationService($this->config, $categories, $settings);
        $this->admin = new AppCustomerServiceCommandService(new DomainAgentService($repository, $mapper, $assignment, $settings, $this->configDomain, $this->config, new \Plugin\CustomerService\Domain\Service\DomainConversationAccessService($categories)), $repository, $outbox, $domain, $faqs);
        $this->provider = $this->createMock(AiProviderInterface::class);
        $this->knowledge = $this->createMock(CustomerServiceKnowledgeInterface::class);
        $this->publisher = $this->createMock(CustomerServiceEventPublisherInterface::class);
        $this->worker = new CustomerServiceWorker($this->config, $settings, new DomainAiReceptionService($this->provider, $this->knowledge), $domain, $outbox, $this->publisher);
    }

    private function enableAi(): void
    {
        $snapshot = $this->configDomain->validate(['settings' => ['ai_enabled' => true, 'default_ai_agent_id' => 1], 'ai_connections' => [['id' => 1, 'name' => '测试', 'endpoint' => 'https://example.com/v1', 'credential_env' => 'CUSTOMER_SERVICE_AI_TEST_KEY', 'enabled' => true]], 'ai_agents' => [['id' => 1, 'name' => 'AI 客服', 'connection_id' => 1, 'model' => 'fake', 'prompt' => '只根据知识回答', 'enabled' => true]]], true);
        $this->config->save($snapshot, 0, 1, true);
    }

    public function testConfigurationPublicationRollbackAndStaleEditUseActualDatabase(): void
    {
        $first = $this->config->save($this->configDomain->validate([]), 0, 1, false);
        self::assertSame(0, $this->config->published()['revision']);
        $second = $this->config->save($first['snapshot'], $first['revision'], 2, true);
        self::assertSame($second['revision'], $this->config->published()['revision']);
        self::assertEquals($first['snapshot'], $this->config->revision($first['revision'])['snapshot']);
        $this->expectException(\DomainException::class);
        $this->config->save([], $first['revision'], 1, false);
    }

    public function testMemberOpenIsReusedAndRetryCannotRewindLastMessage(): void
    {
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        self::assertSame($conversation->id, $this->api->open(1, 'miniapp', '', 2)->id);
        $first = $this->api->sendText(1, $conversation->conversation_no, 'first', '你好');
        $second = $this->api->sendText(1, $conversation->conversation_no, 'second', '问题');
        self::assertSame($first['id'], $this->api->sendText(1, $conversation->conversation_no, 'first', '你好')['id']);
        self::assertSame($second['id'], (int) $conversation->refresh()->last_message_id);
        self::assertSame(2, Db::table('customer_service_messages')->count());
        $this->expectException(\DomainException::class);
        $this->api->sendText(1, $conversation->conversation_no, 'first', '改了内容');
    }

    public function testManualReceptionRetainsReplyTransferAndIdempotentClose(): void
    {
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->admin->accept(11, '客服 A', $conversation->conversation_no);
        $a = CustomerServiceAgent::query()->where('admin_user_id', 11)->first();
        $this->admin->sendText((int) $a->id, $conversation->conversation_no, 'reply', '为您处理');
        $b = $this->admin->updateAgentStatus(12, '客服 B', 'online');
        $this->admin->transfer(11, '客服 A', $conversation->conversation_no, (int) $b->id);
        self::assertSame(0, $a->refresh()->current_conversations);
        self::assertSame(1, $b->refresh()->current_conversations);
        $this->api->close(1, $conversation->conversation_no);
        $this->api->close(1, $conversation->conversation_no);
        self::assertSame(0, $b->refresh()->current_conversations);
        self::assertSame('closed', $conversation->refresh()->reception_state);
    }

    public function testAiAnswerIsAsynchronousEvidenceBoundAndExactlyOnce(): void
    {
        $this->enableAi();
        $this->knowledge->method('search')->willReturn([['id' => 'faq:1', 'text' => '七天内申请']]);
        $this->provider->expects(self::once())->method('answer')->willReturn(new AiAnswerVo('answer', '七天内可申请', evidenceIds: ['faq:1'], tokens: 30));
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        self::assertSame('ai_active', $conversation->reception_state);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '如何退货');
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '如何退货');
        self::assertSame(1, Db::table('customer_service_ai_runs')->count());
        self::assertSame(1, Db::table('customer_service_messages')->count());
        self::assertTrue($this->worker->processOneAiRun());
        self::assertFalse($this->worker->processOneAiRun());
        self::assertSame(1, Db::table('customer_service_messages')->where('sender_type', 'ai')->count());
        self::assertSame(0, CustomerServiceAgent::query()->count());
    }

    public function testNoKnowledgeHandsOffWithoutCallingProvider(): void
    {
        $this->enableAi();
        $this->knowledge->method('search')->willReturn([]);
        $this->provider->expects(self::never())->method('answer');
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '未知问题');
        $this->worker->processOneAiRun();
        self::assertSame('human_waiting', $conversation->refresh()->reception_state);
        self::assertSame('no_knowledge', $conversation->handoff_reason);
    }

    public function testClarificationCountIsPersistedAndLimitHandsOffInsteadOfIncrementingAgain(): void
    {
        $this->enableAi();
        $this->knowledge->method('search')->willReturn([['id' => 'faq:1', 'text' => '退货说明']]);
        $this->provider->expects(self::exactly(2))->method('answer')->willReturn(new AiAnswerVo('clarify', '请提供订单编号', tokens: 10));
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'clarify-1', '如何退货');
        self::assertTrue($this->worker->processOneAiRun());
        self::assertSame(1, $conversation->refresh()->clarification_count);
        self::assertSame('ai_active', $conversation->reception_state);
        $this->api->sendText(1, $conversation->conversation_no, 'clarify-2', '还不清楚');
        self::assertTrue($this->worker->processOneAiRun());
        self::assertSame(1, $conversation->refresh()->clarification_count);
        self::assertSame('human_waiting', $conversation->reception_state);
        self::assertSame('clarification_limit', $conversation->handoff_reason);
        self::assertFalse($this->worker->processOneAiRun());
    }

    public function testOldClientRecoveryAndLateAnswerCannotRaceHumanTakeover(): void
    {
        $this->enableAi();
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '问题');
        $run = (array) Db::table('customer_service_ai_runs')->first();
        Db::table('customer_service_ai_runs')->where('id', $run['id'])->update(['status' => 'processing']);
        $old = $this->api->open(1, 'miniapp', '', 1);
        self::assertSame($conversation->id, $old->id);
        self::assertSame('human_waiting', $old->reception_state);
        $this->worker->finishAiRun($run, new AiAnswerVo('answer', '迟到回复', evidenceIds: ['faq:1']), 20);
        self::assertSame(0, Db::table('customer_service_messages')->where('sender_type', 'ai')->count());
        self::assertSame('cancelled', Db::table('customer_service_ai_runs')->first()->status);
    }

    public function testOutboxPersistsAcrossFailureAndRetry(): void
    {
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '问题');
        $this->publisher->expects(self::atLeastOnce())->method('publish')->willThrowException(new \RuntimeException('temporary'));
        self::assertSame(0, $this->worker->dispatchOutbox());
        self::assertGreaterThan(0, Db::table('customer_service_outbox_events')->whereNull('delivered_at')->where('attempt_count', 1)->count());
    }

    public function testFaqPublicationIsImmutableAndDraftChangesDoNotOverwriteOldAnswer(): void
    {
        $faq = $this->admin->saveFaq(null, ['question' => '多久发货', 'answer' => '48 小时', 'publish' => true]);
        $revisionId = $faq->published_revision_id;
        $this->admin->saveFaq((int) $faq->id, ['question' => '多久发货', 'answer' => '72 小时', 'publish' => true]);
        self::assertSame(2, Db::table('customer_service_faq_revisions')->count());
        self::assertStringContainsString('48 小时', Db::table('customer_service_faq_revisions')->where('id', $revisionId)->first()->content_snapshot);
    }

    public function testSmallBudgetReleasesReservationAndAllowsAnotherRound(): void
    {
        $this->enableAi();
        $draft = $this->config->draft();
        $draft['snapshot']['settings']['ai_daily_token_budget'] = 2000;
        $this->config->save($draft['snapshot'], $draft['revision'], 1, true);
        $this->knowledge->method('search')->willReturn([['id' => 'faq:1', 'text' => '答案']]);
        $this->provider->expects(self::exactly(2))->method('answer')->willReturn(new AiAnswerVo('answer', '答案', evidenceIds: ['faq:1'], tokens: 100));
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '问题一');
        $this->worker->processOneAiRun();
        $this->api->sendText(1, $conversation->conversation_no, 'q2', '问题二');
        $this->worker->processOneAiRun();
        self::assertSame(200, (int) Db::table('customer_service_ai_runs')->sum('tokens'));
        self::assertSame(0, (int) Db::table('customer_service_ai_runs')->sum('reserved_tokens'));
    }

    public function testBudgetReservationsCannotSpendTheSameRemainingCapacity(): void
    {
        $this->enableAi();
        $draft = $this->config->draft();
        $draft['snapshot']['settings']['ai_daily_token_budget'] = 1000;
        $this->config->save($draft['snapshot'], $draft['revision'], 1, true);
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '问题一');
        $this->api->sendText(1, $conversation->conversation_no, 'q2', '问题二');
        Db::table('customer_service_ai_runs')->update(['status' => 'processing']);
        $runs = Db::table('customer_service_ai_runs')->orderBy('id')->pluck('id');
        $results = $this->race([fn () => $this->worker->reserveBudget((int) $runs[0], 200, 400), fn () => $this->worker->reserveBudget((int) $runs[1], 200, 400)]);
        $outputs = array_column($results, 'value');
        sort($outputs);
        self::assertSame([200, 400], $outputs);
        self::assertSame(1000, (int) Db::table('customer_service_ai_runs')->sum('reserved_tokens'));
    }

    public function testAgentDisabledHandsCurrentAiConversationsToHumans(): void
    {
        $this->enableAi();
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $draft = $this->config->draft();
        $draft['snapshot']['ai_agents'][0]['enabled'] = false;
        $this->config->save($draft['snapshot'], $draft['revision'], 1, true);
        $this->worker->maintainQueue();
        self::assertSame('human_waiting', $conversation->refresh()->reception_state);
    }

    public function testInternalMessagesAreNeverPublishedToMembers(): void
    {
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        Db::table('customer_service_outbox_events')->delete();
        (new CustomerServiceOutbox())->messageCreated($conversation, ['visibility' => 'internal', 'client_message_id' => 'note', 'content_json' => ['text' => '内部记录']]);
        self::assertSame(0, Db::table('customer_service_outbox_events')->where('principal_type', 'member')->count());
    }

    public function testConcurrentOpenAndLastCapacityUseDatabaseLocks(): void
    {
        if (!function_exists('pcntl_fork')) { self::markTestSkipped('pcntl is required for process concurrency testing'); }
        $opened = $this->race([
            fn () => (int) $this->api->open(1, 'miniapp', '', 2)->id,
            fn () => (int) $this->api->open(1, 'miniapp', '', 2)->id,
        ]);
        self::assertSame($opened[0]['value'], $opened[1]['value']);
        self::assertSame(1, CustomerServiceConversation::query()->count());
        Db::table('members')->insert(['id' => 2, 'nickname' => '会员2']);
        $first = CustomerServiceConversation::query()->first();
        $second = $this->api->open(2, 'miniapp', '', 2);
        $agent = $this->admin->agent(11, '人工客服');
        $agent->update(['max_conversations' => 1, 'status' => 'online', 'last_heartbeat_at' => date('Y-m-d H:i:s')]);
        $accepted = $this->race([
            fn () => $this->admin->accept(11, '人工客服', $first->conversation_no)->reception_state,
            fn () => $this->admin->accept(11, '人工客服', $second->conversation_no)->reception_state,
        ]);
        self::assertCount(1, array_filter($accepted, static fn ($result) => $result['ok']));
        self::assertSame(1, CustomerServiceAgent::query()->first()->current_conversations);
        self::assertSame(1, CustomerServiceConversation::query()->where('reception_state', 'human_active')->count());
    }

    public function testKeywordAndManualClassificationRerouteOnlyUnacceptedReservations(): void
    {
        $general = $this->admin->updateAgentStatus(11, '通用客服', 'online');
        $refund = $this->admin->updateAgentStatus(12, '退款客服', 'online');
        $snapshot = $this->configDomain->validate([
            'settings' => ['fallback_group_id' => 1],
            'categories' => [['id' => 1, 'name' => '退款', 'code' => 'refund', 'default_group_id' => 2], ['id' => 2, 'name' => '普通问题', 'code' => 'general', 'default_group_id' => 1]],
            'skill_groups' => [['id' => 1, 'name' => '通用组'], ['id' => 2, 'name' => '退款组']],
            'agent_skills' => [['id' => 1, 'agent_id' => (int) $general->id, 'group_id' => 1], ['id' => 2, 'agent_id' => (int) $refund->id, 'group_id' => 2]],
            'keyword_rules' => [['id' => 1, 'name' => '退款识别', 'category_id' => 1, 'match_type' => 'contains', 'include_terms' => ['退款']]],
        ]);
        $this->config->save($snapshot, 0, 1, true);
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        self::assertSame((int) $general->id, $conversation->assigned_agent_id);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '我要退款');
        self::assertSame((int) $refund->id, $conversation->refresh()->assigned_agent_id);
        self::assertSame(0, $general->refresh()->current_conversations);
        self::assertSame(1, $refund->refresh()->current_conversations);
        $this->admin->classify($conversation->conversation_no, 2);
        self::assertSame((int) $general->id, $conversation->refresh()->assigned_agent_id);
        self::assertSame(0, $refund->refresh()->current_conversations);
        $this->admin->accept(11, '通用客服', $conversation->conversation_no);
        $this->admin->classify($conversation->conversation_no, 1);
        self::assertSame('human_active', $conversation->refresh()->reception_state);
        self::assertSame((int) $general->id, $conversation->assigned_agent_id);
        self::assertSame(1, $general->refresh()->current_conversations);
    }

    public function testQueueDisabledStillAssignsOnlineAgentThenDefersWhenNobodyAvailable(): void
    {
        $this->config->save($this->configDomain->validate(['settings' => ['queue_enabled' => false]]), 0, 1, true);
        $agent = $this->admin->updateAgentStatus(11, '在线客服', 'online');
        $first = $this->api->open(1, 'miniapp', '', 2);
        self::assertSame('human_assigned', $first->reception_state);
        $agent->update(['status' => 'offline']);
        Db::table('members')->insert(['id' => 2]);
        $second = $this->api->open(2, 'miniapp', '', 2);
        self::assertSame('human_deferred', $second->reception_state);
        self::assertSame('queue_disabled', $second->handoff_reason);
    }

    public function testDisabledSkillsAndGroupsCannotReadOrManuallyAccept(): void
    {
        $agent = $this->admin->agent(11, '客服');
        $snapshot = $this->configDomain->validate(['settings' => ['assignment_strategy' => 'manual'], 'categories' => [['id' => 1, 'name' => '售后', 'code' => 'after_sale', 'default_group_id' => 1]], 'skill_groups' => [['id' => 1, 'name' => '售后组']], 'agent_skills' => [['id' => 1, 'agent_id' => (int) $agent->id, 'group_id' => 1, 'enabled' => false]]]);
        $this->config->save($snapshot, 0, 1, true);
        $conversation = $this->api->open(1, 'miniapp', '', 2, 1);
        $access = new \Plugin\CustomerService\Domain\Service\DomainConversationAccessService(new DomainCategoryService());
        foreach (['skill', 'group'] as $disabled) {
            self::assertFalse($access->canRead($conversation->toArray(), (int) $agent->id, $this->config->published()['snapshot']));
            try { $this->admin->accept(11, '客服', $conversation->conversation_no); self::fail('disabled skill must reject accept'); }
            catch (\DomainException) { self::assertSame(0, $agent->refresh()->current_conversations); }
            if ($disabled === 'skill') {
                $draft = $this->config->draft();
                $draft['snapshot']['agent_skills'][0]['enabled'] = true;
                $draft['snapshot']['skill_groups'][0]['enabled'] = false;
                $this->config->save($draft['snapshot'], $draft['revision'], 1, true);
            }
        }
    }

    public function testAiAgentFallbackGroupIsUsedAndCanAcceptAfterHandoff(): void
    {
        $this->enableAi();
        $general = $this->admin->updateAgentStatus(11, '全局组', 'online');
        $special = $this->admin->updateAgentStatus(12, 'AI兜底组', 'online');
        $draft = $this->config->draft();
        $draft['snapshot']['settings']['fallback_group_id'] = 1;
        $draft['snapshot']['ai_agents'][0]['fallback_group_id'] = 2;
        $draft['snapshot']['skill_groups'] = [['id' => 1, 'name' => '全局'], ['id' => 2, 'name' => 'AI兜底']];
        $draft['snapshot']['agent_skills'] = [['id' => 1, 'agent_id' => (int) $general->id, 'group_id' => 1], ['id' => 2, 'agent_id' => (int) $special->id, 'group_id' => 2]];
        $draft['snapshot']['categories'] = [['id' => 1, 'name' => '指定组', 'code' => 'specific', 'default_group_id' => 1]];
        $this->config->save($this->configDomain->validate($draft['snapshot']), $draft['revision'], 1, true);
        $first = $this->api->open(1, 'miniapp', '', 2);
        $this->api->requestHuman(1, $first->conversation_no);
        self::assertSame((int) $special->id, $first->refresh()->assigned_agent_id);
        self::assertSame('human_active', $this->admin->accept(12, 'AI兜底组', $first->conversation_no)->reception_state);
        Db::table('members')->insert(['id' => 2]);
        $second = $this->api->open(2, 'miniapp', '', 2, 1);
        $this->api->requestHuman(2, $second->conversation_no);
        self::assertSame((int) $general->id, $second->refresh()->assigned_agent_id);
    }

    public function testLegacySettingsSurviveFirstReadDraftCrudAndPublish(): void
    {
        $legacy = $this->createMock(\App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService::class);
        $legacy->method('get')->willReturn(['enabled' => false, 'max_conversations_per_agent' => 9, 'auto_assign_strategy' => 'round_robin', 'gateway_url' => 'wss://support.example.com', 'socket_path' => '/live', 'obsolete' => true]);
        $resolver = new CustomerServiceSettingsResolver($legacy, $this->config);
        $domain = new DomainConfigurationService($this->config, new DomainCategoryService(), $resolver);
        $application = new \Plugin\CustomerService\Application\Admin\AppCustomerServiceConfigurationService($this->config, $domain, new DomainKeywordService(new DomainCategoryService()), new DomainAiReceptionService($this->provider, $this->knowledge));
        $read = $application->read();
        self::assertFalse($read['snapshot']['settings']['enabled']);
        self::assertSame(9, $read['snapshot']['settings']['max_conversations_per_agent']);
        self::assertSame('round_robin', $read['snapshot']['settings']['assignment_strategy']);
        self::assertArrayNotHasKey('auto_assign_strategy', $read['snapshot']['settings']);
        $edited = $application->edit('skill_groups', null, ['name' => '通用组'], 0, 1);
        $application->save($edited['snapshot'], $edited['revision'], 1, true);
        self::assertSame('wss://support.example.com', $resolver->toArray()['gateway_url']);
        self::assertSame('/live', $resolver->toArray()['socket_path']);
        self::assertFalse($resolver->toArray()['enabled']);
    }

    public function testFaqDraftDoesNotLeakToMembersAndRejectsMissingCategory(): void
    {
        $faq = $this->admin->saveFaq(null, ['question' => '多久发货', 'answer' => '48 小时', 'publish' => true]);
        $this->admin->saveFaq((int) $faq->id, ['question' => '多久发货', 'answer' => '未发布草稿']);
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $message = $this->api->sendFaq(1, $conversation->conversation_no, 'faq-message', (int) $faq->id);
        self::assertSame('48 小时', $message['content_json']['answer']);
        $this->expectException(\DomainException::class);
        $this->admin->saveFaq((int) $faq->id, ['question' => '多久发货', 'answer' => '新答案', 'category_id' => 999]);
    }

    public function testProviderMissingUsageConsumesReservationInsteadOfFreeCalls(): void
    {
        $this->enableAi();
        $this->knowledge->method('search')->willReturn([['id' => 'faq:1', 'text' => '答案']]);
        $this->provider->method('answer')->willReturn(new AiAnswerVo('answer', '答案', evidenceIds: ['faq:1']));
        $conversation = $this->api->open(1, 'miniapp', '', 2);
        $this->api->sendText(1, $conversation->conversation_no, 'q1', '问题');
        $this->worker->processOneAiRun();
        self::assertGreaterThan(0, Db::table('customer_service_ai_runs')->first()->tokens);
        self::assertSame(0, (int) Db::table('customer_service_ai_runs')->first()->reserved_tokens);
    }

    public function testEveryConfigurationResourceCrudEditsOneDraftWithStableIds(): void
    {
        $agent = $this->admin->agent(11, '人工客服');
        $revision = 0;
        $inputs = [
            'skill_groups' => ['name' => '售后组'],
            'categories' => ['name' => '售后', 'code' => 'after_sale', 'default_group_id' => 1],
            'keyword_rules' => ['name' => '退款识别', 'category_id' => 1, 'match_type' => 'contains', 'include_terms' => ['退款']],
            'agent_skills' => ['agent_id' => (int) $agent->id, 'category_id' => 1, 'group_id' => 1],
            'ai_connections' => ['name' => '测试连接', 'endpoint' => 'https://example.com/v1', 'credential_env' => 'CUSTOMER_SERVICE_AI_TEST_KEY'],
            'ai_agents' => ['name' => '测试Agent', 'connection_id' => 1, 'model' => 'fake', 'prompt' => '只根据证据回答', 'category_ids' => [1], 'fallback_group_id' => 1],
        ];
        foreach ($inputs as $resource => $input) {
            $draft = $this->configDomain->edit($resource, null, $input, $revision, 1);
            $revision = $draft['revision'];
            self::assertSame(1, $draft['snapshot'][$resource][0]['id']);
            self::assertSame(0, $this->config->published()['revision']);
        }
        $updated = $this->configDomain->edit('keyword_rules', 1, ['include_terms' => ['退款', '退货']], $revision, 1);
        self::assertSame(['退款', '退货'], $updated['snapshot']['keyword_rules'][0]['include_terms']);
        $deleted = $this->configDomain->edit('keyword_rules', 1, [], $updated['revision'], 1, true);
        $created = $this->configDomain->edit('keyword_rules', null, $inputs['keyword_rules'], $deleted['revision'], 1);
        self::assertSame(2, $created['snapshot']['keyword_rules'][0]['id']);
        $this->expectException(\DomainException::class);
        $this->configDomain->edit('skill_groups', 1, [], $created['revision'], 1, true);
    }

    private function race(array $callbacks): array
    {
        $children = [];
        $database = $this->connection->getDatabaseName();
        foreach ($callbacks as $callback) {
            $file = tempnam(sys_get_temp_dir(), 'cs-race-');
            $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
            $pid = pcntl_fork();
            if ($pid === 0) {
                fclose($pair[0]);
                $this->freshConnection($database);
                fread($pair[1], 1);
                try { $result = ['ok' => true, 'value' => $callback()]; }
                catch (\Throwable $error) { $result = ['ok' => false, 'class' => $error::class, 'message' => $error->getMessage()]; }
                file_put_contents($file, json_encode($result, JSON_THROW_ON_ERROR));
                fclose($pair[1]);
                exit(0);
            }
            fclose($pair[1]);
            $children[] = [$pid, $pair[0], $file];
        }
        foreach ($children as $child) { fwrite($child[1], 'x'); fclose($child[1]); }
        $results = [];
        foreach ($children as $child) {
            pcntl_waitpid($child[0], $status);
            self::assertSame(0, pcntl_wexitstatus($status));
            $results[] = json_decode(file_get_contents($child[2]), true, 512, JSON_THROW_ON_ERROR);
            unlink($child[2]);
        }
        $this->freshConnection($database);
        // Child PDO destructors close inherited descriptors; reconnect the disposable-database administrator too.
        $admin = new \PDO('mysql:unix_socket=' . getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), 'root', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        (new \ReflectionProperty(DatabaseTestCase::class, 'admin'))->setValue($this, $admin);
        return $results;
    }

    private function freshConnection(string $database): void
    {
        $pdo = new \PDO('mysql:unix_socket=' . getenv('MINE_SHOP_TEST_MYSQL_SOCKET') . ';dbname=' . $database, 'root', '', [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->connection = new \Hyperf\Database\MySqlConnection($pdo, $database, '', ['name' => 'default', 'driver' => 'mysql']);
        $this->container->set(\Hyperf\Database\ConnectionResolverInterface::class, new \Hyperf\Database\ConnectionResolver(['default' => $this->connection]));
        $this->container->set(Db::class, new Db($this->container));
    }
}
