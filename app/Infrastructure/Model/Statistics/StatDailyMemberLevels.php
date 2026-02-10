<?php

declare(strict_types=1);

namespace App\Infrastructure\Model\Statistics;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property string $date
 * @property string $level
 * @property int $member_count
 */
class StatDailyMemberLevels extends Model
{
    protected ?string $table = 'stat_daily_member_levels';

    protected array $fillable = ['date', 'level', 'member_count'];

    protected array $casts = ['member_count' => 'integer'];
}
