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

use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $session_id
 * @property int $product_id
 * @property int $product_sku_id
 * @property float $original_price
 * @property float $seckill_price
 * @property int $quantity
 * @property int $sold_quantity
 * @property int $max_quantity_per_user
 * @property int $sort_order
 * @property bool $is_enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeckillProduct extends Model
{
    protected ?string $table = 'mall_seckill_session_products';

    protected array $fillable = [
        'activity_id',
        'session_id',
        'product_id',
        'product_sku_id',
        'original_price',
        'seckill_price',
        'quantity',
        'sold_quantity',
        'max_quantity_per_user',
        'sort_order',
        'is_enabled',
    ];

    protected array $casts = [
        'activity_id' => 'integer',
        'session_id' => 'integer',
        'product_id' => 'integer',
        'product_sku_id' => 'integer',
        'original_price' => 'decimal:2',
        'seckill_price' => 'decimal:2',
        'quantity' => 'integer',
        'sold_quantity' => 'integer',
        'max_quantity_per_user' => 'integer',
        'sort_order' => 'integer',
        'is_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SeckillActivity::class, 'activity_id', 'id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(SeckillSession::class, 'session_id', 'id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function productSku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'product_sku_id', 'id');
    }
}
