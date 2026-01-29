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

namespace App\Infrastructure\Library\DataPermission\Scope;

use App\Infrastructure\Library\DataPermission\Factory;
use App\Interface\Common\CurrentUser;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\Scope;

class DataScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = CurrentUser::ctxUser();
        if (empty($user)) {
            return;
        }

        Factory::make()->build($builder->getQuery(), $user);
    }
}
