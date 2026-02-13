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

namespace App\Infrastructure\Model\Review;

use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property int $order_item_id
 * @property int $product_id
 * @property int $sku_id
 * @property int $member_id
 * @property int $rating
 * @property string $content
 * @property null|array $images
 * @property bool $is_anonymous
 * @property string $status
 * @property null|string $admin_reply
 * @property null|Carbon $reply_time
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class Review extends Model
{
    use SoftDeletes;

    protected ?string $table = 'reviews';

    protected array $fillable = [
        'order_id', 'order_item_id', 'product_id', 'sku_id', 'member_id',
        'rating', 'content', 'images', 'is_anonymous', 'status',
        'admin_reply', 'reply_time',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'product_id' => 'integer',
        'sku_id' => 'integer',
        'member_id' => 'integer',
        'rating' => 'integer',
        'images' => 'array',
        'is_anonymous' => 'boolean',
        'reply_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member_id', 'id');
    }
}
