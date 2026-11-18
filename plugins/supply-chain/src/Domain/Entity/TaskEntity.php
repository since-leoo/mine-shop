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

namespace Plugin\SupplyChain\Domain\Entity;

use App\Infrastructure\Error\SupplyChainError;
use Plugin\SupplyChain\Domain\Contract\TaskInput;

final class TaskEntity
{
    private array $dirty = [];

    public function __construct(private array $state = []) {}

    public function create(TaskInput $input): void
    {
        $data = $input->toArray();
        if ($data['connection_id'] < 1 || $data['business_key'] === '') {
            throw SupplyChainError::TASK_PARAMETERS_INVALID->invalid();
        }
        $this->change($data + ['status' => 'pending', 'attempt_count' => 0, 'dispatch_version' => 0, 'max_attempts' => 6, 'trace_id' => bin2hex(random_bytes(16)), 'next_retry_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function claim(int $version, int $now): ?string
    {
        if (! \in_array($this->state['status'], ['pending', 'retry_wait'], true) || (int) $this->state['attempt_count'] >= (int) $this->state['max_attempts'] || (int) $this->state['dispatch_version'] !== $version || strtotime($this->state['next_retry_at'] ?? '1970-01-01') > $now) {
            return null;
        }
        $token = bin2hex(random_bytes(16));
        $this->change(['status' => 'running', 'lease_token' => $token, 'lease_expires_at' => date('Y-m-d H:i:s', $now + 180), 'attempt_count' => (int) $this->state['attempt_count'] + 1, 'updated_at' => date('Y-m-d H:i:s', $now)]);
        return $token;
    }

    public function complete(string $token, array $result, ?int $now = null): bool
    {
        if (! $this->owns($token) || strtotime($this->state['lease_expires_at']) <= ($now ?? time())) {
            return false;
        }
        $this->change(['status' => 'succeeded', 'result' => $result, 'last_error' => null, 'lease_token' => null, 'lease_expires_at' => null, 'next_retry_at' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function fail(string $token, string $reason, bool $uncertain, bool $permanent, int $now): bool
    {
        if (! $this->owns($token)) {
            return false;
        }
        $attempt = (int) $this->state['attempt_count'];
        $status = $uncertain ? 'unknown' : (($permanent || $attempt >= (int) $this->state['max_attempts']) ? 'failed' : 'retry_wait');
        $delay = [30, 120, 600, 1800, 7200][min(4, max(0, $attempt - 1))];
        $this->change(['status' => $status, 'last_error' => $reason, 'lease_token' => null, 'lease_expires_at' => null, 'next_retry_at' => $status === 'retry_wait' ? date('Y-m-d H:i:s', $now + $delay + random_int(0, 15)) : null, 'last_dispatched_at' => null, 'dispatch_version' => (int) $this->state['dispatch_version'] + 1, 'updated_at' => date('Y-m-d H:i:s', $now)]);
        return true;
    }

    public function retry(): void
    {
        if ($this->state['status'] === 'unknown') {
            throw SupplyChainError::UNKNOWN_WRITE_REPLAY_FORBIDDEN->exception();
        }
        if (! \in_array($this->state['status'], ['failed', 'retry_wait'], true)) {
            throw SupplyChainError::TASK_RETRY_FORBIDDEN->exception();
        }
        $this->change(['status' => 'pending', 'next_retry_at' => date('Y-m-d H:i:s'), 'last_dispatched_at' => null, 'dispatch_version' => (int) $this->state['dispatch_version'] + 1, 'max_attempts' => max((int) $this->state['max_attempts'], (int) $this->state['attempt_count'] + 3), 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function snapshot(): array
    {
        return $this->state;
    }

    public function toArray(): array
    {
        return $this->dirty;
    }

    private function owns(string $token): bool
    {
        return $this->state['status'] === 'running' && hash_equals((string) $this->state['lease_token'], $token);
    }

    private function change(array $data): void
    {
        $this->state = array_replace($this->state, $data);
        $this->dirty = array_replace($this->dirty, $data);
    }
}
