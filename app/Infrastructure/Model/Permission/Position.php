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

use App\Domain\Permission\Enum\DataPermission\PolicyType;
use App\Domain\Permission\ValueObject\SetDataPermissionVo;
use App\Infrastructure\Model\DataPermission\Policy;
use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\BelongsToMany;
use Hyperf\Database\Model\Relations\HasOne;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name 岗位名称
 * @property int $dept_id 部门ID
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property null|Department $department
 * @property Collection<int,User>|User[] $users
 * @property Policy $policy
 */
class Position extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'position';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'dept_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'dept_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'dept_id', 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_position', 'position_id', 'user_id');
    }

    public function policy(): HasOne
    {
        return $this->hasOne(Policy::class, 'position_id', 'id');
    }

    /**
     * 设置数据权限策略（实体行为方法）.
     *
     * @param PolicyType $policyType 策略类型
     * @param array $value 策略值
     * @throws \DomainException
     */
    public function setDataPermissionPolicy(PolicyType $policyType, array $value): SetDataPermissionVo
    {
        // 2. 返回需要持久化的数据
        return new SetDataPermissionVo(
            success: true,
            message: '数据权限设置成功',
            policyType: $policyType,
            value: $value
        );
    }

    /**
     * 检查是否可以设置数据权限.
     */
    public function canSetDataPermission(): bool
    {
        return $this->dept_id !== null;
    }
}
