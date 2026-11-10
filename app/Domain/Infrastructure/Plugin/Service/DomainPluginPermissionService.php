<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Infrastructure\Plugin\Repository\PluginPermissionRepository;

final class DomainPluginPermissionService
{
    public function __construct(private readonly PluginPermissionRepository $repository) {}

    public function register(string $group, array $permissions): void
    {
        if (! preg_match('/^[a-z][a-z0-9-]{1,39}$/D', $group)) {
            throw new \InvalidArgumentException('插件权限组标识无效');
        }
        foreach ($permissions as $code => $title) {
            if (! is_string($code) || strlen($code) > 50 || ! str_starts_with($code, $group . ':') || ! preg_match('~^[a-z0-9:/-]+$~D', $code)) {
                throw new \InvalidArgumentException('插件权限码必须使用本插件命名空间且不超过50字节');
            }
            if (! is_string($title) || trim($title) === '' || mb_strlen($title) > 100) {
                throw new \InvalidArgumentException('插件权限名称不能为空或超过100字符');
            }
        }
        $this->repository->register($group, $permissions);
    }
}
