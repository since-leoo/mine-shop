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

namespace Plugin\SupplyChain\Infrastructure\Persistence;

use Plugin\SupplyChain\Domain\Repository\SupplyRepository;
use Plugin\SupplyChain\Domain\ValueObject\SafeLogVo;
use Plugin\SupplyChain\Interfaces\ApiLogRepositoryInterface;

final class DatabaseApiLogRepository implements ApiLogRepositoryInterface
{
    public function __construct(private readonly SupplyRepository $repository) {}

    public function begin(array $intent): int|string
    {
        return $this->repository->insert('api_logs', [
            'connection_id' => $intent['connection_id'], 'task_id' => $intent['task_id'] ?? null,
            'attempt_id' => $intent['attempt_id'] ?? null, 'request_id' => $intent['request_id'],
            'trace_id' => $intent['trace_id'] ?? $intent['request_id'], 'operation' => $intent['operation'] ?? '',
            'method' => $intent['method'], 'path' => $intent['path'], 'status' => 'started',
            'request_data' => SafeLogVo::sanitize($intent['request_data'] ?? []), 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function finish(int|string $id, array $outcome): void
    {
        $this->repository->update('api_logs', (int) $id, [
            'status' => match ($outcome['status'] ?? 'unknown') {
                'success' => 'succeeded', 'rejected' => 'failed', default => 'unknown'
            },
            'http_status' => $outcome['http_status'] ?? null,
            'provider_code' => isset($outcome['provider_code']) ? (string) $outcome['provider_code'] : null,
            'response_data' => SafeLogVo::sanitize($outcome['response_data'] ?? []),
            'error_message' => isset($outcome['error_message']) ? mb_substr((string) SafeLogVo::sanitize($outcome['error_message']), 0, 1000) : null,
            'duration_ms' => $outcome['duration_ms'] ?? null, 'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
