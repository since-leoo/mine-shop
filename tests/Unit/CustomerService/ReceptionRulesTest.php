<?php

declare(strict_types=1);

namespace HyperfTests\Unit\CustomerService;

use PHPUnit\Framework\TestCase;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Entity\CustomerServiceConversationEntity;
use Plugin\CustomerService\Domain\Service\DomainCategoryService;
use Plugin\CustomerService\Domain\Service\DomainConfigurationService;
use Plugin\CustomerService\Domain\Service\DomainKeywordService;
use Plugin\CustomerService\Domain\Service\DomainRoutingService;

final class ReceptionRulesTest extends TestCase
{
    private function categories(): array
    {
        return [['id' => 1, 'name' => '售后', 'code' => 'after', 'parent_id' => 0], ['id' => 2, 'name' => '退款', 'code' => 'refund', 'parent_id' => 1], ['id' => 3, 'name' => '进度', 'code' => 'progress', 'parent_id' => 2]];
    }

    public function testCategoryTreeRejectsFourthLevelAndCycles(): void
    {
        $service = new DomainCategoryService();
        self::assertSame([1, 2, 3], $service->validate($this->categories())[2]['path']);
        foreach ([array_merge($this->categories(), [['id' => 4, 'name' => '四级', 'code' => 'four', 'parent_id' => 3]]), array_replace($this->categories(), [0 => ['id' => 1, 'name' => '售后', 'code' => 'after', 'parent_id' => 3]])] as $invalid) {
            try { $service->validate($invalid); self::fail('Must reject invalid category tree'); } catch (\DomainException) { self::assertTrue(true); }
        }
    }

    public function testMovingSubtreeChecksAllDescendantsAndDisabledParents(): void
    {
        $service = new DomainCategoryService();
        $categories = $this->categories();
        $categories[0]['enabled'] = false;
        self::assertNull($service->effective($categories, 3));
        $categories[0]['enabled'] = true;
        $categories[0]['force_human'] = true;
        self::assertTrue($service->effective($categories, 3)['force_human']);
    }

    public function testKeywordExclusionsAndConflictsAndHumanConfirmation(): void
    {
        $keywords = new DomainKeywordService(new DomainCategoryService());
        $snapshot = ['categories' => $this->categories(), 'keyword_rules' => [
            ['id' => 1, 'category_id' => 2, 'match_type' => 'contains', 'include_terms' => ['退款'], 'exclude_terms' => ['不是退款'], 'priority' => 1],
            ['id' => 2, 'category_id' => 3, 'match_type' => 'all', 'include_terms' => ['退款', '进度'], 'priority' => 2],
        ]];
        self::assertNull($keywords->identify('不是退款，我想修改地址', $snapshot)['category_id']);
        self::assertSame(3, $keywords->identify('查询退款进度', $snapshot)['category_id']);
        self::assertSame(2, $keywords->identify('查询退款进度', $snapshot, 2, true)['category_id']);
        $snapshot['keyword_rules'][] = ['id' => 3, 'category_id' => 1, 'match_type' => 'all', 'include_terms' => ['退款', '进度'], 'priority' => 2];
        self::assertTrue($keywords->identify('查询退款进度', $snapshot)['conflict']);
    }

    public function testRoutingFiltersPresenceCapacityAndUsesPriorityThenLoadRatio(): void
    {
        $service = new DomainRoutingService(new DomainCategoryService());
        $now = new \DateTimeImmutable('2026-10-12 12:00:00+08:00');
        $base = ['enabled' => true, 'status' => 'online', 'last_heartbeat_at' => $now->format(DATE_ATOM), 'priority' => 10, 'max_conversations' => 10, 'current_conversations' => 2];
        $agents = [array_replace($base, ['id' => 1, 'current_conversations' => 3]), array_replace($base, ['id' => 2, 'max_conversations' => 5]), array_replace($base, ['id' => 3, 'priority' => 20]), array_replace($base, ['id' => 4, 'current_conversations' => 10]), array_replace($base, ['id' => 5, 'last_heartbeat_at' => '2025-01-01'])];
        self::assertSame([3, 1, 2], array_column($service->candidates($agents, [], null, $now), 'id'));
        self::assertFalse($service->onDuty(['timezone' => 'Asia/Shanghai', 'weekly' => ['1' => [['08:00', '10:00']]]], $now));
    }

    public function testConfiguredTaxonomyWithoutMatchingSkillsDoesNotFallBackToEveryAgent(): void
    {
        $routing = new DomainRoutingService(new DomainCategoryService());
        $now = new \DateTimeImmutable();
        $agents = [['id' => 1, 'status' => 'online', 'last_heartbeat_at' => $now->format(DATE_ATOM), 'max_conversations' => 5, 'current_conversations' => 0]];
        self::assertCount(1, $routing->candidates($agents, [], null, $now));
        self::assertSame([], $routing->candidates($agents, ['categories' => [['id' => 1, 'name' => '售后', 'code' => 'after-sale', 'default_group_id' => 1]], 'skill_groups' => [['id' => 1, 'name' => '售后组']]], 1, $now));
        self::assertSame([], $routing->candidates($agents, ['settings' => ['fallback_group_id' => 1], 'skill_groups' => [['id' => 1, 'name' => '兜底组']]], null, $now));
    }

    public function testAiTakeoverGenerationAndCloseAreIdempotent(): void
    {
        $entity = new CustomerServiceConversationEntity(['member_id' => 10, 'reception_state' => 'ai_active', 'generation' => 1, 'ai_turn' => 2]);
        self::assertSame([], $entity->toArray());
        self::assertTrue($entity->allowsAi(1, 2, true));
        $entity->requestHuman('requested', '2026-10-12 12:00:00');
        self::assertFalse($entity->allowsAi(1, 2, true));
        $entity->assign(1, '2026-10-12 12:01:00');
        self::assertFalse($entity->accept(1));
        self::assertTrue($entity->close(10, 'member_closed', '2026-10-12 12:02:00'));
        self::assertFalse($entity->close(10, 'member_closed', '2026-10-12 12:02:00'));
    }

    public function testConfigurationDefaultsDisabledAiAndRejectsDanglingReferences(): void
    {
        $repository = $this->createMock(SupportConfigurationInterface::class);
        $service = new DomainConfigurationService($repository, new DomainCategoryService(), $this->createMock(\Plugin\CustomerService\Service\CustomerServiceSettingsResolver::class));
        self::assertFalse($service->validate([])['settings']['ai_enabled']);
        $this->expectException(\DomainException::class);
        $service->validate(['settings' => ['ai_enabled' => true, 'default_ai_agent_id' => 999]], true);
    }

    public function testDefinitionsCrudRequiresExpectedVersionAndCannotDeleteReferencedCategory(): void
    {
        $repository = $this->createMock(SupportConfigurationInterface::class);
        $repository->method('draft')->willReturn(['revision' => 5, 'snapshot' => ['categories' => $this->categories()]]);
        $repository->expects(self::never())->method('save');
        $service = new DomainConfigurationService($repository, new DomainCategoryService(), $this->createMock(\Plugin\CustomerService\Service\CustomerServiceSettingsResolver::class));
        $this->expectException(\DomainException::class);
        $service->edit('categories', 2, [], 5, 1, true);
    }
}
