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

namespace App\Infrastructure\Model\Product;

use Carbon\Carbon;
use Hyperf\Database\Model\Events\Creating;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku_code
 * @property string $sku_name
 * @property null|array $spec_values
 * @property null|string $image
 * @property int $cost_price
 * @property int $market_price
 * @property int $sale_price
 * @property int $stock
 * @property int $warning_stock
 * @property float $weight
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProductSku extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const CODE_PREFIX = 'SKU';

    protected ?string $table = 'product_skus';

    protected array $fillable = [
        'product_id',
        'sku_code',
        'sku_name',
        'spec_values',
        'image',
        'cost_price',
        'market_price',
        'sale_price',
        'stock',
        'warning_stock',
        'weight',
        'status',
    ];

    protected array $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'spec_values' => 'array',
        'cost_price' => 'integer',
        'market_price' => 'integer',
        'sale_price' => 'integer',
        'stock' => 'integer',
        'warning_stock' => 'integer',
        'weight' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        if (empty($this->sku_code)) {
            $this->sku_code = self::generateSkuCode();
        }
        if (empty($this->status)) {
            $this->status = self::STATUS_ACTIVE;
        }
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock <= warning_stock');
    }

    public static function generateSkuCode(): string
    {
        $prefix = self::CODE_PREFIX;
        $timestamp = date('YmdHis');
        $random = mb_str_pad((string) mt_rand(0, 9999), 4, '0', \STR_PAD_LEFT);

        return $prefix . $timestamp . $random;
    }
}
