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

namespace App\Infrastructure\Model\Seckill;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $title
 * @property null|string $description
 * @property string $status
 * @property bool $is_enabled
 * @property null|array $rules
 * @property null|string $remark
 * @property null|int $created_by
 * @property null|int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeckillActivity extends Model
{
    protected ?string $table = 'seckill_activities';

    protected array $fillable = ['title', 'description', 'status', 'is_enabled', 'rules', 'remark', 'created_by', 'updated_by'];

    protected array $casts = [
        'is_enabled' => 'boolean', 'rules' => 'array',
        'created_by' => 'integer', 'updated_by' => 'integer',
        'created_at' => 'datetime', 'updated_at' => 'datetime',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(SeckillSession::class, 'activity_id', 'id');
    }
}
