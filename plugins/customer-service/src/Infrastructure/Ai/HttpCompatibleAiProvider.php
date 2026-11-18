<?php

declare(strict_types=1);

namespace Plugin\CustomerService\Infrastructure\Ai;

use App\Infrastructure\Error\CustomerServiceError;
use GuzzleHttp\Client;
use Plugin\CustomerService\Domain\Contract\AiProviderInterface;
use Plugin\CustomerService\Domain\ValueObject\AiAnswerVo;
use Plugin\CustomerService\Domain\ValueObject\AiPromptVo;

final class HttpCompatibleAiProvider implements AiProviderInterface
{
    public function answer(array $connection, array $agent, array $messages, array $evidence): AiAnswerVo
    {
        $host = strtolower((string) parse_url($connection['endpoint'], PHP_URL_HOST));
        $allowed = array_filter(array_map('trim', explode(',', (string) getenv('CUSTOMER_SERVICE_AI_ALLOWED_HOSTS'))));
        if (!in_array($host, $allowed, true)) { throw CustomerServiceError::AI_HOST_NOT_ALLOWED->exception(); }
        $addresses = dns_get_record($host, DNS_A | DNS_AAAA) ?: [];
        if (!$addresses) { throw CustomerServiceError::AI_HOST_UNRESOLVABLE->exception(); }
        foreach ($addresses as $address) {
            $ip = $address['ip'] ?? $address['ipv6'] ?? '';
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { throw CustomerServiceError::AI_PRIVATE_ADDRESS_FORBIDDEN->exception(); }
        }
        $key = getenv((string) $connection['credential_env']);
        if (!$key) { throw CustomerServiceError::AI_CREDENTIAL_MISSING->exception(); }
        $chat = AiPromptVo::messages($agent, $messages, $evidence);
        $outputLimit = min(4096, max(128, (int) ($agent['max_output_tokens'] ?? 1024)));
        if (!extension_loaded('curl')) { throw CustomerServiceError::CURL_EXTENSION_REQUIRED->internal(); }
        $address = $addresses[0]['ip'] ?? $addresses[0]['ipv6'];
        // 固定刚刚检查过的公网地址，防止验证后再次解析时被改为内网地址。
        $resolved = str_contains($address, ':') ? '[' . $address . ']' : $address;
        $client = new Client(['allow_redirects' => false, 'timeout' => min(60, max(1, (int) ($agent['timeout_seconds'] ?? 15))), 'connect_timeout' => 5, 'verify' => true, 'handler' => new \GuzzleHttp\Handler\CurlHandler(), 'curl' => [CURLOPT_RESOLVE => [$host . ':443:' . $resolved]], 'proxy' => '']);
        try {
            $response = $client->post(rtrim($connection['endpoint'], '/') . '/chat/completions', [
                'headers' => ['Authorization' => 'Bearer ' . $key, 'Content-Type' => 'application/json'],
                'json' => ['model' => $agent['model'], 'messages' => $chat, 'response_format' => ['type' => 'json_object'], 'max_tokens' => $outputLimit],
            ]);
        } catch (\Throwable) { throw CustomerServiceError::AI_REQUEST_FAILED->internal(); }
        return $this->parseResponse((string) $response->getBody(), array_slice($evidence, 0, 4), AiPromptVo::inputUpperBound($agent, $messages, $evidence) + $outputLimit);
    }

    private function parseResponse(string $body, array $evidence, int $fallbackTokens): AiAnswerVo
    {
        // 错误答案同样可能产生调用费用；转人工及沙盒结果也必须保留用量，缺失时保守计费。
        $tokens = max(1, $fallbackTokens);
        if (strlen($body) > 262144) { return new AiAnswerVo('handoff', reason: 'invalid_response', tokens: $tokens); }
        try {
            $result = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($result)) { return new AiAnswerVo('handoff', reason: 'invalid_response', tokens: $tokens); }
            $usage = filter_var($result['usage']['total_tokens'] ?? null, FILTER_VALIDATE_INT);
            if ($usage !== false && $usage > 0) { $tokens = $usage; }
            $content = $result['choices'][0]['message']['content'] ?? null;
            if (!is_string($content)) { return new AiAnswerVo('handoff', reason: 'invalid_response', tokens: $tokens); }
            $answer = json_decode($content, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return new AiAnswerVo('handoff', reason: 'invalid_response', tokens: $tokens);
        }
        if (!is_array($answer) || !in_array($answer['action'] ?? null, ['answer', 'clarify', 'handoff'], true)
            || !is_string($answer['text'] ?? '') || !is_string($answer['reason'] ?? '')
            || !is_array($answer['evidence_ids'] ?? [])) {
            return new AiAnswerVo('handoff', reason: 'invalid_response', tokens: $tokens);
        }
        $action = $answer['action'];
        $text = mb_substr($answer['text'] ?? '', 0, 4000);
        if ($action !== 'handoff' && trim($text) === '') { return new AiAnswerVo('handoff', reason: 'invalid_response', tokens: $tokens); }
        $ids = [];
        foreach ($answer['evidence_ids'] ?? [] as $id) {
            if (!is_string($id) && !is_int($id)) { return new AiAnswerVo('handoff', reason: 'invalid_evidence', tokens: $tokens); }
            $ids[] = (string) $id;
        }
        $ids = array_values(array_unique($ids));
        if (array_diff($ids, array_map('strval', array_column($evidence, 'id')))) {
            return new AiAnswerVo('handoff', reason: 'invalid_evidence', tokens: $tokens);
        }
        if ($action === 'answer' && !$ids) { return new AiAnswerVo('handoff', reason: 'missing_evidence', tokens: $tokens); }
        return new AiAnswerVo($action, $text, mb_substr($answer['reason'] ?? '', 0, 100), $ids, $tokens);
    }
}
