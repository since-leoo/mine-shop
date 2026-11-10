<?php

declare(strict_types=1);

namespace HyperfTests\Unit\CustomerService;

use PHPUnit\Framework\TestCase;
use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;
use Plugin\CustomerService\Infrastructure\Ai\HttpCompatibleAiProvider;

final class AiResponseTest extends TestCase
{
    private function parse(string $body): AiAnswerVo
    {
        return (new \ReflectionMethod(HttpCompatibleAiProvider::class, 'parseResponse'))->invoke(new HttpCompatibleAiProvider(), $body, [['id' => 'faq:1', 'text' => '支持七天退货']], 2048);
    }

    private function envelope(array $answer, mixed $usage = 123): string
    {
        return json_encode(['choices' => [['message' => ['content' => json_encode($answer, JSON_THROW_ON_ERROR)]]], 'usage' => ['total_tokens' => $usage]], JSON_THROW_ON_ERROR);
    }

    public function testCitedAnswerPreservesProviderUsage(): void
    {
        $answer = $this->parse($this->envelope(['action' => 'answer', 'text' => '支持七天退货', 'evidence_ids' => ['faq:1']]));
        self::assertSame('answer', $answer->action);
        self::assertSame(['faq:1'], $answer->evidenceIds);
        self::assertSame(123, $answer->tokens);
    }

    public function testUnusableUsageChargesConservativeBound(): void
    {
        foreach ([null, 0, -1, [], 'unknown'] as $usage) {
            self::assertSame(2048, $this->parse($this->envelope(['action' => 'handoff'], $usage))->tokens);
        }
        self::assertSame(2048, $this->parse('{"choices":[{"message":{"content":"{\"action\":\"handoff\"}"}}]}')->tokens);
    }

    public function testMissingOrInventedCitationsHandOffWithoutLosingUsage(): void
    {
        foreach ([[], ['faq:1', 'invented'], [['id' => 'faq:1']]] as $ids) {
            $answer = $this->parse($this->envelope(['action' => 'answer', 'text' => '回复', 'evidence_ids' => $ids]));
            self::assertSame('handoff', $answer->action);
            self::assertSame(123, $answer->tokens);
        }
    }

    public function testMalformedResponsesFailClosedAndRetainKnownUsage(): void
    {
        foreach ([['action' => []], ['action' => 'delete'], ['action' => 'answer', 'text' => []], ['action' => 'clarify', 'text' => ' '], ['action' => 'answer', 'text' => '回复', 'evidence_ids' => 'faq:1']] as $payload) {
            $answer = $this->parse($this->envelope($payload));
            self::assertSame('handoff', $answer->action);
            self::assertSame(123, $answer->tokens);
        }
        $answer = $this->parse('{"choices":[{"message":{"content":"not-json"}}],"usage":{"total_tokens":99}}');
        self::assertSame('handoff', $answer->action);
        self::assertSame(99, $answer->tokens);
        self::assertSame(2048, $this->parse('not-json')->tokens);
        self::assertSame(2048, $this->parse(str_repeat('x', 262145))->tokens);
    }
}
