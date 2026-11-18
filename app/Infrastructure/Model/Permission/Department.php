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

namespace App\Infrastructure\Model\Permission;

use Carbon\Carbon;
use Hyperf\Collection\Collection as BaseCollection;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name 部门名称
 * @property int $parent_id 父级部门ID
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Collection<int, Position> $positions 岗位
 * @property Collection<int, User> $department_users 部门用户
 * @property Collection<int, User> $leader 部门领导
 * @property Collection<int, Department> $children 子部门
 */
class Department extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'department';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'parent_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'parent_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function deleted(Deleted $event): void
    {
        $this->positions()->delete();
        $this->department_users()->detach();
        $this->leader()->detach();
    }

    /** @return HasMany<Position, $this> */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'dept_id', 'id');
    }

    /** @return BelongsToMany<User, $this> */
    public function department_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_dept', 'dept_id', 'user_id');
    }

    /** @return BelongsToMany<User, $this> */
    public function leader(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dept_leader', 'dept_id', 'user_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        $relation = $this->hasMany(self::class, 'parent_id', 'id');
        $relation->with(['children', 'positions']);
        return $relation;
    }

    /** @return BaseCollection<int, Department> */
    public function getFlatChildren(): BaseCollection
    {
        /** @var BaseCollection<int, Department> $flat */
        $flat = collect();
        $this->load('children'); // 预加载子部门
        $traverse = static function ($departments) use (&$traverse, $flat) {
            foreach ($departments as $department) {
                $flat->push($department);
                if ($department->children->isNotEmpty()) {
                    $traverse($department->children);
                }
            }
        };
        $traverse($this->children);
        return $flat->prepend($this); // 包含自身
    }
}
