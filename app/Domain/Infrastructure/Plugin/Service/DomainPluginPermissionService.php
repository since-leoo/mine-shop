<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Infrastructure\Plugin\Repository\PluginPermissionRepository;

use App\Infrastructure\Error\PluginError;

final class DomainPluginPermissionService
{
    public function __construct(private readonly PluginPermissionRepository $repository) {}

    public function register(string $group, array $permissions): void
    {
        if (! preg_match('/^[a-z][a-z0-9-]{1,39}$/D', $group)) {
            throw PluginError::PERMISSION_GROUP_INVALID->invalid();
        }
        foreach ($permissions as $code => $title) {
            if (! is_string($code) || strlen($code) > 50 || ! str_starts_with($code, $group . ':') || ! preg_match('~^[a-z0-9:/-]+$~D', $code)) {
                throw PluginError::PERMISSION_CODE_INVALID->invalid();
            }
            if (! is_string($title) || trim($title) === '' || mb_strlen($title) > 100) {
                throw PluginError::PERMISSION_NAME_INVALID->invalid();
            }
        }
        $this->repository->register($group, $permissions);
    }
}
