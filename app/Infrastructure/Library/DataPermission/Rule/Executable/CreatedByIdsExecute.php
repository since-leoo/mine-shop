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

namespace App\Infrastructure\Library\DataPermission\Rule\Executable;

use App\Infrastructure\Model\Permission\User;

class CreatedByIdsExecute extends AbstractExecutable
{
    public function execute(): ?array
    {
        $policy = $this->getPolicy();
        if ($policy->policy_type->isAll()) {
            return null;
        }
        if ($policy->policy_type->isSelf()) {
            return [(int) $this->getUser()->id];
        }
        $departments = (new DeptExecute($this->getUser(), $policy))->execute() ?? [];
        if ($departments === []) {
            return [];
        }
        return User::query()->where(static function ($query) use ($departments): void {
            $query->whereHas('department', static fn ($dept) => $dept->whereIn('department.id', $departments))
                ->orWhereHas('position', static fn ($position) => $position->whereIn('position.dept_id', $departments))
                ->orWhereHas('dept_leader', static fn ($dept) => $dept->whereIn('department.id', $departments));
        })
            ->pluck('id')->map(static fn ($id) => (int) $id)->all();
    }
}
