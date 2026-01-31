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

namespace Hyperf\DbConnection\Model;

use Hyperf\Database\Model\Builder as ModelBuilder;

if (false) {
    /**
     * @mixin ModelBuilder
     *
     * @method static ModelBuilder query()
     * @method static ModelBuilder where(mixed $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
     * @method static ModelBuilder whereIn(mixed $column, array $values, string $boolean = 'and', bool $not = false)
     * @method static Model|static create(array $attributes = [])
     * @method static int count(string $columns = '*')
     * @method static Model|static|null find(mixed $id, array $columns = ['*'])
     * @method static mixed max(string $column)
     * @method static ModelBuilder active()
     */
    abstract class Model {}
}
