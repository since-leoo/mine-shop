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

namespace App\Infrastructure\Model\Shipping;

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
 * @property null|int $created_by
 * @property null|int $updated_by
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
        'created_by',
        'updated_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'rules' => 'array',
        'free_rules' => 'array',
        'is_default' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
