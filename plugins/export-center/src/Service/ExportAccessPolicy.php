<?php

declare(strict_types=1);

namespace Plugin\ExportCenter\Service;

use App\Domain\Permission\Enum\DataPermission\PolicyType;
use App\Infrastructure\Library\DataPermission\Rule\Executable\CreatedByIdsExecute;
use App\Infrastructure\Library\DataPermission\Rule\Executable\DeptExecute;
use App\Infrastructure\Model\Permission\User;

final class ExportAccessPolicy
{
    public function fingerprint(User $user): string
    {
        if ($user->status->isDisable()) {
            throw new \DomainException('导出任务操作者已停用');
        }
        $policy = $user->getPolicy();
        $superAdmin = $user->isSuperAdmin();
        if (! $superAdmin && $policy?->policy_type === PolicyType::CustomFunc) {
            throw new \DomainException('动态自定义数据权限暂不支持持久化导出，请使用明确的数据范围策略');
        }
        $creators = $superAdmin ? null : ($policy ? (new CreatedByIdsExecute($user, $policy))->execute() : [$user->id]);
        $departments = $superAdmin ? null : ($policy ? (new DeptExecute($user, $policy))->execute() : []);
        if ($creators !== null) {
            sort($creators);
        }
        if ($departments !== null) {
            sort($departments);
        }
        $permissions = $user->getPermissions()->map(static fn ($menu) => [$menu->id, $menu->name, $menu->status])->all();
        sort($permissions);
        return hash('sha256', json_encode([
            'user' => $user->id,
            'super_admin' => $superAdmin,
            'roles' => $user->roles()->orderBy('role.id')->get(['role.id', 'role.code', 'role.status'])->toArray(),
            'permissions' => $permissions,
            'policy' => $policy ? [$policy->policy_type->value, $policy->value] : null,
            'creators' => $creators,
            'departments' => $departments,
        ], JSON_THROW_ON_ERROR));
    }
}
