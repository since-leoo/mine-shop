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

namespace App\Infrastructure\Model\Order;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property string $action
 * @property null|string $description
 * @property string $operator_type
 * @property int $operator_id
 * @property null|string $operator_name
 * @property null|string $old_status
 * @property null|string $new_status
 * @property null|array $extra_data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderLog extends Model
{
    public const ACTION_CREATE = 'create';

    public const OPERATOR_SYSTEM = 'system';

    protected ?string $table = 'order_logs';

    protected array $fillable = [
        'order_id',
        'action',
        'description',
        'operator_type',
        'operator_id',
        'operator_name',
        'old_status',
        'new_status',
        'extra_data',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'operator_id' => 'integer',
        'extra_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
