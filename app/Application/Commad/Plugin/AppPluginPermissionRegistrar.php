<?php

declare(strict_types=1);

namespace App\Application\Commad\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\PluginPermissionRegistrarInterface;
use App\Domain\Infrastructure\Plugin\Service\DomainPluginPermissionService;
use App\Domain\Trade\Shared\Contract\TransactionManagerInterface;

final class AppPluginPermissionRegistrar implements PluginPermissionRegistrarInterface
{
    public function __construct(private readonly DomainPluginPermissionService $service, private readonly TransactionManagerInterface $transactions) {}

    public function register(string $group, array $permissions): void
    {
        $this->transactions->run(fn () => $this->service->register($group, $permissions));
    }
}
