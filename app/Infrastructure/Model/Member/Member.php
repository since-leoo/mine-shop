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

namespace App\Infrastructure\Model\Member;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $openid
 * @property null|string $unionid
 * @property null|string $nickname
 * @property null|string $avatar
 * @property string $gender
 * @property null|string $phone
 * @property null|Carbon $birthday
 * @property null|string $city
 * @property null|string $province
 * @property null|string $country
 * @property string $level
 * @property int $growth_value
 * @property int $total_orders
 * @property float $total_amount
 * @property null|Carbon $last_login_at
 * @property null|string $last_login_ip
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Member extends Model
{
    protected ?string $table = 'mall_members';

    protected array $fillable = [
        'openid',
        'unionid',
        'nickname',
        'avatar',
        'gender',
        'phone',
        'birthday',
        'city',
        'province',
        'country',
        'level',
        'growth_value',
        'total_orders',
        'total_amount',
        'last_login_at',
        'last_login_ip',
        'status',
    ];

    protected array $casts = [
        'birthday' => 'date',
        'growth_value' => 'integer',
        'total_orders' => 'integer',
        'total_amount' => 'decimal:2',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
