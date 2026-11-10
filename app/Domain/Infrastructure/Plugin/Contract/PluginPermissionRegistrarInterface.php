<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

/** 插件仅声明权限码；菜单存储和角色授权归核心权限模块所有。 */
interface PluginPermissionRegistrarInterface
{
    /**
     * 幂等注册隐藏权限组与按钮，不删除旧权限，不自动授予任何角色。
     * @param array<string, string> $permissions 完整权限码 => 中文名称；权限码必须以 "$group:" 开头
     */
    public function register(string $group, array $permissions): void;
}
