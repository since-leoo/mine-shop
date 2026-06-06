<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Infrastructure\AuditLog;

use App\Domain\Infrastructure\AuditLog\Service\AuditModeContextEnricher;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class AuditModeContextEnricherTest extends TestCase
{
    public function testEnrichReturnsPayloadUnchangedWhenAuditModeDisabled(): void
    {
        $enricher = new AuditModeContextEnricher($this->mallSettings(false));
        $payload = ['remark' => 'manual update', 'method' => 'POST'];

        self::assertSame($payload, $enricher->enrich($payload, ['user_id' => 5]));
    }

    public function testEnrichAppendsBoundedJsonContextWhenAuditModeEnabled(): void
    {
        $enricher = new AuditModeContextEnricher($this->mallSettings(true));

        $payload = $enricher->enrich([
            'remark' => 'manual update',
            'method' => 'POST',
            'router' => '/admin/orders/10',
        ], [
            'user_id' => 5,
            'path' => '/admin/orders/10',
            'operation' => 'order.update',
            'ip' => '127.0.0.1',
        ]);

        self::assertStringStartsWith('manual update | audit=', $payload['remark']);
        self::assertLessThanOrEqual(255, strlen($payload['remark']));
        self::assertStringContainsString('"audit_mode":true', $payload['remark']);
        self::assertStringContainsString('"user_id":5', $payload['remark']);
        self::assertStringContainsString('"operation":"order.update"', $payload['remark']);
    }

    private function mallSettings(bool $auditMode): DomainMallSettingService
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting(
            'aliyun',
            [],
            ['mail' => true, 'system' => true],
            '',
            '',
            '',
            $auditMode
        ));

        return $settings;
    }
}
