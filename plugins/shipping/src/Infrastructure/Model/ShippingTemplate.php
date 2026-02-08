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

namespace Plugin\Since\Shipping\Infrastructure\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $charge_type
 * @property null|array $rules
 * @property null|array $free_rules
 * @property bool $is_default
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ShippingTemplate extends Model
{
    protected ?string $table = 'shipping_templates';

    protected array $fillable = [
        'name',
        'charge_type',
        'rules',
        'free_rules',
        'is_default',
        'status',
    ];

    protected array $casts = [
        'id' => 'integer',
        'rules' => 'array',
        'free_rules' => 'array',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
