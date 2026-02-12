<?php

declare(strict_types=1);

namespace App\Infrastructure\Model\Seckill;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\DbConnection\Model\Model;
use App\Domain\Trade\Seckill\Enum\SeckillStatus;

/**
 * @property int $id
 * @property int $activity_id
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property string $status
 * @property int $max_quantity_per_user
 * @property int $total_quantity
 * @property int $sold_quantity
 * @property int $sort_order
 * @property bool $is_enabled
 * @property null|array $rules
 * @property null|string $remark
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class SeckillSession extends Model
{
    protected ?string $table = 'seckill_sessions';

    protected array $fillable = [
        'activity_id', 'start_time', 'end_time', 'status', 'max_quantity_per_user',
        'total_quantity', 'sold_quantity', 'sort_order', 'is_enabled', 'rules', 'remark',
    ];

    protected array $casts = [
        'start_time' => 'datetime', 'end_time' => 'datetime',
        'max_quantity_per_user' => 'integer', 'total_quantity' => 'integer',
        'sold_quantity' => 'integer', 'sort_order' => 'integer',
        'is_enabled' => 'boolean', 'rules' => 'array',
        'created_at' => 'datetime', 'updated_at' => 'datetime',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SeckillActivity::class, 'activity_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(SeckillProduct::class, 'session_id', 'id');
    }

    public function getDynamicStatus(): SeckillStatus
    {
        $now = Carbon::now();
        if (! $this->is_enabled) {
            return SeckillStatus::CANCELLED;
        }
        if ($this->sold_quantity >= $this->total_quantity && $this->total_quantity > 0) {
            return SeckillStatus::SOLD_OUT;
        }
        if ($now->lt($this->start_time)) {
            return SeckillStatus::PENDING;
        }
        if ($now->gt($this->end_time)) {
            return SeckillStatus::ENDED;
        }
        return SeckillStatus::ACTIVE;
    }
}
