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
 * @property string $package_no
 * @property null|string $express_company
 * @property null|string $express_no
 * @property string $status
 * @property float $weight
 * @property null|string $remark
 * @property null|Carbon $shipped_at
 * @property null|Carbon $delivered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderPackage extends Model
{
    protected ?string $table = 'mall_order_packages';

    protected array $fillable = [
        'order_id',
        'package_no',
        'express_company',
        'express_no',
        'status',
        'weight',
        'remark',
        'shipped_at',
        'delivered_at',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'weight' => 'decimal:3',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
