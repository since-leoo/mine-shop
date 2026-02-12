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

namespace App\Infrastructure\Model\GroupBuy;

use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $title
 * @property null|string $description
 * @property int $product_id
 * @property int $sku_id
 * @property int $original_price
 * @property int $group_price
 * @property int $min_people
 * @property int $max_people
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property int $group_time_limit
 * @property string $status
 * @property int $total_quantity
 * @property int $sold_quantity
 * @property int $group_count
 * @property int $success_group_count
 * @property int $sort_order
 * @property bool $is_enabled
 * @property null|array $rules
 * @property null|array $images
 * @property null|string $remark
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class GroupBuy extends Model
{
    use SoftDeletes;

    protected ?string $table = 'group_buys';

    protected array $fillable = [
        'title', 'description', 'product_id', 'sku_id',
        'original_price', 'group_price', 'min_people', 'max_people',
        'start_time', 'end_time', 'group_time_limit', 'status',
        'total_quantity', 'sold_quantity', 'group_count', 'success_group_count',
        'sort_order', 'is_enabled', 'rules', 'images', 'remark',
    ];

    protected array $casts = [
        'product_id' => 'integer', 'sku_id' => 'integer',
        'original_price' => 'integer', 'group_price' => 'integer',
        'min_people' => 'integer', 'max_people' => 'integer',
        'start_time' => 'datetime', 'end_time' => 'datetime',
        'group_time_limit' => 'integer', 'total_quantity' => 'integer',
        'sold_quantity' => 'integer', 'group_count' => 'integer',
        'success_group_count' => 'integer', 'sort_order' => 'integer',
        'is_enabled' => 'boolean', 'rules' => 'array', 'images' => 'array',
        'created_at' => 'datetime', 'updated_at' => 'datetime', 'deleted_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSku::class, 'sku_id');
    }
}
