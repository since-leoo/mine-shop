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
 * @property string $image_url
 * @property null|string $alt_text
 * @property int $sort_order
 * @property bool $is_primary
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ProductGallery extends Model
{
    protected ?string $table = 'product_gallery';

    protected array $fillable = [
        'product_id',
        'image_url',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected array $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('sort_order', $direction);
    }
}
