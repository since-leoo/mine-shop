<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Application\Admin\Infrastructure;

use App\Application\Admin\Infrastructure\AppUserOperationLogCommandService;
use App\Application\Admin\Infrastructure\Listener\UserOperationSubscriber;
use App\Application\Admin\Permission\AppUserQueryService;
use App\Domain\Infrastructure\AuditLog\Service\AuditModeContextEnricher;
use App\Infrastructure\Model\Permission\User;
use App\Interface\Common\Event\RequestOperationEvent;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class UserOperationSubscriberAuditModeTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testProcessEnrichesOperationPayloadBeforeCreatingLog(): void
    {
        $service = $this->createMock(AppUserOperationLogCommandService::class);
        $userQueryService = $this->createMock(AppUserQueryService::class);
        $enricher = $this->createMock(AuditModeContextEnricher::class);

        $user = new class extends User {
            public function __construct() {}
        };
        $user->setRawAttributes(['id' => 5, 'username' => 'admin'], true);
        $userQueryService->method('find')->with(5)->willReturn($user);

        $enricher->expects(self::once())
            ->method('enrich')
            ->with(
                self::callback(static fn (array $payload): bool => $payload['username'] === 'admin' && $payload['service_name'] === 'order.update'),
                self::callback(static fn (array $context): bool => $context['user_id'] === 5 && $context['operation'] === 'order.update')
            )
            ->willReturn([
                'username' => 'admin',
                'method' => 'POST',
                'router' => '/admin/orders/10',
                'remark' => 'manual update | audit={"audit_mode":true}',
                'ip' => '127.0.0.1',
                'service_name' => 'order.update',
            ]);

        $service->expects(self::once())
            ->method('create')
            ->with(self::callback(static fn (array $payload): bool => str_contains($payload['remark'], 'audit=')));

        $subscriber = new UserOperationSubscriber($service, $userQueryService, $enricher);
        $subscriber->process(new RequestOperationEvent(5, 'order.update', '/admin/orders/10', '127.0.0.1', 'POST', 'manual update'));
    }
}
