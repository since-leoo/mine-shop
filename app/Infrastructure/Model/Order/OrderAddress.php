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

use App\Infrastructure\Model\Concerns\LoadsRelations;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property string $receiver_name
 * @property string $receiver_phone
 * @property string $province
 * @property string $city
 * @property string $district
 * @property string $detail
 * @property string $full_address
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class OrderAddress extends Model
{
    use LoadsRelations;

    protected ?string $table = 'order_addresses';

    protected array $fillable = [
        'order_id',
        'receiver_name',
        'receiver_phone',
        'province',
        'city',
        'district',
        'detail',
        'full_address',
    ];

    protected array $casts = [
        'order_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $appends = [
        'name',
        'phone',
        'address',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function getNameAttribute(): string
    {
        return (string) $this->receiver_name;
    }

    public function getPhoneAttribute(): string
    {
        return (string) $this->receiver_phone;
    }

    public function getAddressAttribute(): string
    {
        return (string) $this->detail;
    }
}
