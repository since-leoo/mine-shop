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
 * @property int $product_id
 * @property int $sku_id
 * @property string $product_name
 * @property string $sku_name
 * @property null|string $product_image
 * @property null|array $spec_values
 * @property float $unit_price
 * @property int $quantity
 * @property float $total_price
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderItem extends Model
{
    protected ?string $table = 'mall_order_items';

    protected array $fillable = [
        'order_id',
        'product_id',
        'sku_id',
        'product_name',
        'sku_name',
        'product_image',
        'spec_values',
        'unit_price',
        'quantity',
        'total_price',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'spec_values' => 'array',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'total_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
