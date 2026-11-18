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

namespace Plugin\SupplyChain\Infrastructure\Sdk\Yunzhong;

use App\Infrastructure\Error\SupplyChainError;
use GuzzleHttp\ClientInterface;
use Hyperf\Context\Context;
use Plugin\SupplyChain\Infrastructure\Security\LogSanitizer;
use Plugin\SupplyChain\Interfaces\ApiLogRepositoryInterface;
use Plugin\SupplyChain\Interfaces\RequestLimiterInterface;
use Plugin\SupplyChain\Interfaces\TokenStoreInterface;
use Plugin\SupplyChain\Service\ConnectionConfig;

/** Internal protocol transport. Application callers use named capability interfaces. */
final class YunzhongClient
{
    public const OUTCOME_UNKNOWN = 10001;

    public const AUDIT_UNAVAILABLE = 10002;

    public function __construct(
        private readonly ConnectionConfig $config,
        private readonly ClientInterface $http,
        private readonly TokenStoreInterface $tokens,
        private readonly ApiLogRepositoryInterface $logs,
        private readonly RequestLimiterInterface $limiter,
        private readonly Signer $signer = new Signer(),
        private readonly LogSanitizer $sanitizer = new LogSanitizer(),
    ) {}

    public function call(string $operation, string $method, string $path, array $parameters = [], bool $readOnly = true): mixed
    {
        $token = $this->token();
        try {
            return $this->execute($operation, $method, $path, $parameters, $token);
        } catch (\DomainException $error) {
            if ($error->getCode() !== 6) {
                throw $error;
            }
            $this->tokens->forgetIfEquals($this->config->tokenKey(), $token);
            // At most one refresh retry, only for reads. Purchase and after-sale writes never replay inside SDK.
            if (! $readOnly) {
                throw $error;
            }
            return $this->execute($operation, $method, $path, $parameters, $this->token());
        }
    }

    private function token(): string
    {
        $key = $this->config->tokenKey();
        $cached = $this->tokens->get($key);
        if ($cached !== null) {
            return $cached;
        }
        $owner = bin2hex(random_bytes(16));
        if (! $this->tokens->lock($key, $owner, (int) ceil($this->config->timeout) + 10)) {
            // Avoid a thundering herd; callers may reschedule. Do not hold a task's lease while waiting for another refresher.
            throw new \RuntimeException(SupplyChainError::TOKEN_REFRESH_IN_PROGRESS->message(), 429);
        }
        try {
            $cached = $this->tokens->get($key);
            if ($cached !== null) {
                return $cached;
            }
            $data = $this->execute('token.refresh', 'POST', '/app/application/getToken', [
                'app_key' => $this->config->appKey, 'app_secret' => $this->config->appSecret,
            ], null);
            $token = \is_array($data) ? ($data['token'] ?? null) : null;
            $expiresAt = \is_array($data) ? ($data['expiresAt'] ?? null) : null;
            if (! \is_string($token) || $token === '' || ! is_numeric($expiresAt)) {
                throw SupplyChainError::TOKEN_RESPONSE_INVALID->internal();
            }
            $ttl = (int) floor(((float) $expiresAt - microtime(true) * 1000) / 1000) - 30;
            if ($ttl <= 0) {
                throw SupplyChainError::TOKEN_EXPIRY_INVALID->internal();
            }
            $this->tokens->put($key, $token, $ttl);
            return $token;
        } finally {
            $this->tokens->unlock($key, $owner);
        }
    }

    private function execute(string $operation, string $method, string $path, array $parameters, ?string $token): mixed
    {
        if (! \in_array($method, ['GET', 'POST'], true) || ! preg_match('#^/app/[a-zA-Z0-9/]+$#', $path)) {
            throw SupplyChainError::API_ENDPOINT_INVALID->invalid();
        }
        $requestId = bin2hex(random_bytes(16));
        $timestamp = (string) (int) floor(microtime(true) * 1000);
        $parameters['message_id'] = base64_encode('YZ' . $timestamp . bin2hex(random_bytes(4)));
        $signatureParameters = [
            'Api-App-Key' => $this->config->appKey,
            'Api-Time-Stamp' => $timestamp,
            'Api-Nonce' => $this->signer->nonce($this->config->appKey, $timestamp),
        ];
        if ($token !== null) {
            $signatureParameters['x-token'] = $token;
        }
        $body = '';
        if ($method === 'GET') {
            $signatureParameters = array_merge($signatureParameters, $parameters);
        } else {
            // Encode exactly once: these bytes are both signed and sent. Keep the legacy JSON escape conventions.
            $body = json_encode($parameters, \JSON_THROW_ON_ERROR);
        }
        $signature = $this->signer->sign($signatureParameters, $this->config->appSecret, $body);
        $headers = ['Api-Time-Stamp' => $timestamp, 'Api-Sign' => $signature,
            'appKey' => $this->config->appKey, 'appSecret' => $this->config->appSecret,
            'Content-Type' => 'application/json', 'Accept' => 'application/json'];
        if ($token !== null) {
            $headers['x-token'] = $token;
        }
        // Api-Nonce is signed but omitted from headers, as in the scanned active client.
        $query = $method === 'GET' ? array_merge($signatureParameters, ['Api-Sign' => $signature]) : [];
        $secrets = array_merge(
            [$this->config->appKey, $this->config->appSecret, $token ?? '', $signature],
            $this->sanitizer->sensitiveValues($parameters)
        );
        $requestData = $this->sanitizer->sanitize(['query' => $query, 'body' => $parameters, 'headers' => $headers], $secrets);
        $intent = ['request_id' => $requestId, 'trace_id' => Context::get('supply_chain.trace_id', $requestId),
            'task_id' => Context::get('supply_chain.task_id'), 'attempt_id' => Context::get('supply_chain.attempt_id'),
            'connection_id' => $this->config->id, 'operation' => $operation, 'method' => $method,
            'path' => $path, 'status' => 'intent', 'request_data' => $requestData];
        // Fail closed: if intent persistence fails, no HTTP side effect is started.
        try {
            $logId = $this->logs->begin($intent);
        } catch (\Throwable) {
            throw new \RuntimeException(SupplyChainError::REQUEST_AUDIT_WRITE_FAILED->message(), self::AUDIT_UNAVAILABLE);
        }
        $started = microtime(true);
        $outcome = ['status' => 'unknown', 'http_status' => null, 'provider_code' => null,
            'response_data' => null, 'error_message' => null];
        $lease = null;
        $stage = 'limiter';
        try {
            $lease = $this->limiter->acquire($this->config->id, (int) ceil($this->config->timeout) + 10);
            $stage = 'transport';
            $options = ['headers' => $headers, 'timeout' => $this->config->timeout,
                'connect_timeout' => $this->config->connectTimeout, 'verify' => true,
                'allow_redirects' => false, 'http_errors' => false];
            if ($method === 'GET') {
                $options['query'] = http_build_query($query, '', '&', \PHP_QUERY_RFC1738);
            } else {
                $options['body'] = $body;
            }
            $response = $this->http->request($method, rtrim($this->config->baseUrl, '/') . $path, $options);
            $stage = 'response';
            $outcome['http_status'] = $response->getStatusCode();
            $raw = (string) $response->getBody();
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $data = json_decode($raw, true);
                $outcome['response_data'] = \is_array($data) ? $this->sanitizer->sanitize($data, $secrets)
                    : ['format' => 'non_json', 'bytes' => mb_strlen($raw, '8bit'), 'sha256' => hash('sha256', $raw)];
                throw new \RuntimeException(SupplyChainError::HTTP_STATUS_INVALID->message([$response->getStatusCode()]), self::OUTCOME_UNKNOWN);
            }
            $decoded = json_decode($raw, true, 512, \JSON_THROW_ON_ERROR);
            if (! \is_array($decoded) || ! \array_key_exists('code', $decoded)
                || (! \is_int($decoded['code']) && ! \is_string($decoded['code']))
                || ! preg_match('/^-?[0-9]+$/', (string) $decoded['code'])) {
                throw new \RuntimeException(SupplyChainError::RESPONSE_STATUS_CODE_INVALID->message(), self::OUTCOME_UNKNOWN);
            }
            $outcome['response_data'] = $this->sanitizer->sanitize($decoded, $secrets);
            $outcome['provider_code'] = (int) $decoded['code'];
            if ((int) $decoded['code'] !== 0) {
                $outcome['status'] = 'rejected';
                $message = $this->sanitizer->sanitize((string) ($decoded['msg'] ?? SupplyChainError::UPSTREAM_REQUEST_REJECTED->message()), $secrets);
                throw new \DomainException(\is_string($message) ? $message : SupplyChainError::UPSTREAM_REJECTION_DETAILS->message(), (int) $decoded['code']);
            }
            $outcome['status'] = 'success';
            return $decoded['data'] ?? [];
        } catch (\DomainException $error) {
            $outcome['error_message'] = $error->getMessage();
            throw $error;
        } catch (\Throwable $error) {
            // Guzzle exception text may contain credential-bearing GET URLs. Never chain or stringify it.
            $outcome['status'] = $lease === null ? 'rejected' : 'unknown';
            $outcome['error_message'] = match ($stage) {
                'limiter' => SupplyChainError::LIMITER_UNAVAILABLE->message(),
                'transport' => SupplyChainError::TRANSPORT_OUTCOME_UNKNOWN->message(),
                default => SupplyChainError::RESPONSE_OUTCOME_UNKNOWN->message([$outcome['http_status'] ?? 'unknown']),
            };
            throw new \RuntimeException(
                SupplyChainError::REQUEST_FAILURE_REFERENCE->message([$outcome['error_message'], $requestId]),
                $lease === null ? 429 : self::OUTCOME_UNKNOWN
            );
        } finally {
            $outcome['duration_ms'] = (int) round((microtime(true) - $started) * 1000);
            try {
                $this->logs->finish($logId, $outcome);
            } catch (\Throwable) {
                // The durable unfinished intent lets recovery classify an uncertain remote write.
                throw new \RuntimeException(SupplyChainError::RESULT_AUDIT_WRITE_FAILED->message([$requestId]), self::OUTCOME_UNKNOWN);
            } finally {
                if ($lease !== null) {
                    try {
                        $this->limiter->release($this->config->id, $lease);
                    } catch (\Throwable) {
                        // Expiring admission leases recover automatically; never replace an acknowledged upstream result.
                    }
                }
            }
        }
    }
}
