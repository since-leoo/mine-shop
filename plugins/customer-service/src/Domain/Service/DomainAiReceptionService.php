<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Domain\Service;

use Plugin\CustomerService\Domain\Contract\AiProviderInterface;
use Plugin\CustomerService\Domain\Contract\CustomerServiceKnowledgeInterface;
use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;

final class DomainAiReceptionService
{
    public function __construct(private readonly AiProviderInterface $provider, private readonly CustomerServiceKnowledgeInterface $knowledge) {}

    public function answer(array $snapshot, array $run, array $conversation, array $messages): AiAnswerVo
    {
        $prepared = $this->prepare($snapshot, $run, $conversation, $messages);
        return $prepared instanceof AiAnswerVo ? $prepared : $this->complete($prepared);
    }

    public function prepare(array $snapshot, array $run, array $conversation, array $messages): array|AiAnswerVo
    {
        $agent = array_column($snapshot['ai_agents'] ?? [], null, 'id')[(int) $run['agent_id']] ?? null;
        if (!$agent || !($agent['enabled'] ?? true)) { return new AiAnswerVo('handoff', reason: 'agent_unavailable'); }
        $connection = array_column($snapshot['ai_connections'] ?? [], null, 'id')[(int) $agent['connection_id']] ?? null;
        if (!$connection || !($connection['enabled'] ?? true)) { return new AiAnswerVo('handoff', reason: 'connection_unavailable'); }
        $question = '';
        foreach ($messages as $message) { if ((int) $message['id'] === (int) $run['trigger_message_id']) { $question = (string) ($message['content_json']['text'] ?? ''); } }
        $evidence = $this->knowledge->search($question, $agent, isset($conversation['category_id']) ? (int) $conversation['category_id'] : null, (int) $conversation['member_id']);
        if (!$evidence) { return new AiAnswerVo('handoff', reason: 'no_knowledge'); }
        $agent['timeout_seconds'] = $snapshot['settings']['ai_timeout_seconds'] ?? 15;
        return ['connection' => $connection, 'agent' => $agent, 'messages' => $messages, 'evidence' => $evidence];
    }

    public function complete(array $prepared): AiAnswerVo
    {
        $evidence = $prepared['evidence'];
        $answer = $this->provider->answer($prepared['connection'], $prepared['agent'], $prepared['messages'], $evidence);
        if ($answer->action === 'answer' && (!$answer->evidenceIds || array_diff($answer->evidenceIds, array_map('strval', array_column($evidence, 'id'))))) { return new AiAnswerVo('handoff', reason: 'invalid_evidence'); }
        return $answer;
    }
}
