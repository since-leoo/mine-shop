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
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $product_code
 * @property int $category_id
 * @property null|int $brand_id
 * @property string $name
 * @property null|string $sub_title
 * @property null|string $main_image
 * @property null|array $gallery_images
 * @property null|string $description
 * @property null|string $detail_content
 * @property null|array $attributes
 * @property int $min_price
 * @property int $max_price
 * @property int $virtual_sales
 * @property int $real_sales
 * @property bool $is_recommend
 * @property bool $is_hot
 * @property bool $is_new
 * @property null|int $shipping_template_id
 * @property null|string $freight_type
 * @property null|int $flat_freight_amount
 * @property int $sort
 * @property string $status
 * @property null|int $created_by
 * @property null|int $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class Product extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_SOLD_OUT = 'sold_out';

    public const CODE_PREFIX = 'PRD';

    protected ?string $table = 'products';

    protected array $fillable = [
        'product_code',
        'category_id',
        'brand_id',
        'name',
        'sub_title',
        'main_image',
        'gallery_images',
        'description',
        'detail_content',
        'attributes',
        'min_price',
        'max_price',
        'virtual_sales',
        'real_sales',
        'is_recommend',
        'is_hot',
        'is_new',
        'freight_type',
        'flat_freight_amount',
        'shipping_template_id',
        'sort',
        'status',
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'gallery_images' => 'array',
        'attributes' => 'array',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'virtual_sales' => 'integer',
        'real_sales' => 'integer',
        'is_recommend' => 'boolean',
        'is_hot' => 'boolean',
        'is_new' => 'boolean',
        'shipping_template_id' => 'integer',
        'freight_type' => 'string',
        'flat_freight_amount' => 'integer',
        'sort' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(ProductSku::class, 'product_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'product_id');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ProductGallery::class, 'product_id');
    }

    public function creating(Creating $event): void
    {
        if (empty($this->product_code)) {
            $this->product_code = self::CODE_PREFIX . '-' . mb_strtoupper(mb_str_pad(uniqid(), 5, '0', \STR_PAD_LEFT));
        }
    }
}
