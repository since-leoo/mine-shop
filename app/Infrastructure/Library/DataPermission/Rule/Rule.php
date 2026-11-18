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

namespace App\Infrastructure\Library\DataPermission\Rule;

use App\Infrastructure\Error\PermissionError;

use App\Infrastructure\Library\DataPermission\Rule\Executable\CreatedByIdsExecute;
use App\Infrastructure\Library\DataPermission\Rule\Executable\DeptExecute;
use App\Infrastructure\Library\DataPermission\ScopeType;
use App\Infrastructure\Model\DataPermission\Policy;
use App\Infrastructure\Model\Permission\User;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Query\Builder;

class Rule
{
    public function __construct(private readonly ConfigInterface $config) {}

    public function getDeptIds(User $user, Policy $policy): ?array
    {
        return (new DeptExecute($user, $policy))->execute();
    }

    public function getCreatedByList(User $user, Policy $policy): ?array
    {
        return (new CreatedByIdsExecute($user, $policy))->execute();
    }

    public function loadCustomFunc(string $customFunc, Builder $builder, User $user, ?Policy $policy, ScopeType $scopeType): void
    {
        $func = $this->config->get('department.custom.' . $customFunc);
        if ($func === null) {
            throw PermissionError::NAMED_FUNCTION_NOT_FOUND->internal([$customFunc]);
        }
        $func($builder, $scopeType, $policy, $user);
    }
}
