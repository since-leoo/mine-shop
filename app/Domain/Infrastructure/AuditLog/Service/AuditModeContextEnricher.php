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

namespace App\Domain\Infrastructure\AuditLog\Service;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;

final class AuditModeContextEnricher
{
    private const REMARK_LIMIT = 255;

    public function __construct(private readonly DomainMallSettingService $mallSettingService) {}

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function enrich(array $payload, array $context): array
    {
        if (! $this->mallSettingService->integration()->auditMode()) {
            return $payload;
        }

        $audit = [
            'audit_mode' => true,
            'user_id' => $context['user_id'] ?? null,
            'operation' => $context['operation'] ?? $payload['service_name'] ?? null,
            'path' => $context['path'] ?? $payload['router'] ?? null,
            'method' => $context['method'] ?? $payload['method'] ?? null,
            'ip' => $context['ip'] ?? $payload['ip'] ?? null,
        ];
        $audit = array_filter($audit, static fn (mixed $value): bool => $value !== null && $value !== '');

        $remark = trim((string) ($payload['remark'] ?? ''));
        $suffix = 'audit=' . json_encode($audit, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $payload['remark'] = $this->limitRemark($remark === '' ? $suffix : $remark . ' | ' . $suffix);

        return $payload;
    }

    private function limitRemark(string $remark): string
    {
        if (strlen($remark) <= self::REMARK_LIMIT) {
            return $remark;
        }

        return substr($remark, 0, self::REMARK_LIMIT);
    }
}
