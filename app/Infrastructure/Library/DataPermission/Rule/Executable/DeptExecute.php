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

class DeptExecute extends AbstractExecutable
{
    public function execute(): ?array
    {
        $policy = $this->getPolicy();
        $type = $policy->policy_type;
        if ($type->isAll()) {
            return null;
        }
        if ($type->isCustomDept()) {
            return array_values(array_unique(array_map('intval', $policy->value ?? [])));
        }
        $departments = $this->getUser()->department()->get()->merge($this->getUser()->dept_leader()->get());
        foreach ($this->getUser()->position()->get() as $position) {
            $departments = $departments->merge($position->department()->get());
        }
        $ids = $departments->pluck('id')->all();
        if ($type->isDeptTree()) {
            foreach ($departments as $department) {
                $ids = array_merge($ids, $department->getFlatChildren()->pluck('id')->all());
            }
        }
        return array_values(array_unique(array_map('intval', $ids)));
    }
}
