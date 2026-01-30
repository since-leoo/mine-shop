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
use Hyperf\Database\Model\Events\Updating;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku_code
 * @property string $sku_name
 * @property null|array $spec_values
 * @property null|string $image
 * @property float $cost_price
 * @property float $market_price
 * @property float $sale_price
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

    protected ?string $table = 'mall_product_skus';

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
        'cost_price' => 'decimal:2',
        'market_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'warning_stock' => 'integer',
        'weight' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creating(Creating $event): void
    {
        if (empty($this->sku_code)) {
            $this->sku_code = $this->resolveUniqueCode();
        } elseif (! self::isCodeUnique($this->sku_code)) {
            throw new \InvalidArgumentException('SKU编码已存在');
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
        $random = str_pad((string) mt_rand(0, 9999), 4, '0', \STR_PAD_LEFT);

        return $prefix . $timestamp . $random;
    }

    public static function isCodeUnique(string $code, int $excludeId = 0): bool
    {
        $query = self::where('sku_code', $code);

        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count() === 0;
    }

    private function resolveUniqueCode(): string
    {
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; ++$i) {
            $candidate = self::generateSkuCode();
            if (self::isCodeUnique($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('无法生成唯一SKU编码');
    }
}
