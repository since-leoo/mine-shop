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
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $product_id
 * @property string $attribute_name
 * @property string $value
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProductAttribute extends Model
{
    protected ?string $table = 'product_attributes';

    protected array $fillable = [
        'product_id',
        'attribute_name',
        'value',
    ];

    protected array $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
