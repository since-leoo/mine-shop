<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Application\Admin;

use App\Infrastructure\Error\CustomerServiceError;
use Plugin\CustomerService\Domain\Contract\SupportConfigurationInterface;
use Plugin\CustomerService\Domain\Service\DomainConfigurationService;
use Plugin\CustomerService\Domain\Service\DomainKeywordService;

final class AppCustomerServiceConfigurationService
{
    public function __construct(private readonly SupportConfigurationInterface $repository, private readonly DomainConfigurationService $domain, private readonly DomainKeywordService $keywords, private readonly \Plugin\CustomerService\Domain\Service\DomainAiReceptionService $ai) {}

    public function read(): array
    {
        $draft = $this->repository->draft();
        $draft['snapshot'] = $this->domain->defaults($draft['snapshot']);
        return $draft + ['published_revision' => $this->repository->published()['revision'], 'history' => $this->repository->history()];
    }

    public function save(array $snapshot, int $expectedRevision, int $operatorId, bool $publish): array
    {
        return $this->repository->save($this->domain->validate($snapshot, $publish), $expectedRevision, $operatorId, $publish);
    }

    public function edit(string $resource, ?int $id, array $input, int $expectedRevision, int $operatorId, bool $delete = false): array
    {
        return $this->domain->edit($resource, $id, $input, $expectedRevision, $operatorId, $delete);
    }

    public function rollback(int $revision, int $expectedRevision, int $operatorId): array
    {
        $old = $this->repository->revision($revision);
        return $this->save($old['snapshot'], $expectedRevision, $operatorId, true);
    }

    public function simulate(string $question, ?int $categoryId = null): array
    {
        return $this->keywords->identify($question, $this->domain->defaults($this->repository->draft()['snapshot']), $categoryId);
    }

    public function testAgent(int $agentId, string $question): array
    {
        if ($question === '' || mb_strlen($question) > 2000) { throw CustomerServiceError::QUESTION_TRIAL_LENGTH_INVALID->invalid(); }
        $snapshot = $this->domain->validate($this->repository->draft()['snapshot']);
        foreach ($snapshot['ai_agents'] as &$agent) { $agent['tool_allowlist'] = []; }
        unset($agent);
        $answer = $this->ai->answer($snapshot, ['agent_id' => $agentId, 'trigger_message_id' => 1], ['member_id' => 0], [['id' => 1, 'sender_type' => 'member', 'message_type' => 'text', 'content_json' => ['text' => $question]]]);
        return ['action' => $answer->action, 'text' => $answer->text, 'reason' => $answer->reason, 'evidence_ids' => $answer->evidenceIds, 'tokens' => $answer->tokens, 'sandbox' => true];
    }
}
