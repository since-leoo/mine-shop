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

namespace Plugin\ExportCenter\Model;

use App\Infrastructure\Model\Permission\User;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property null|string $ip_address
 * @property null|string $user_agent
 * @property Carbon $downloaded_at
 */
class ExportDownloadLog extends Model
{
    public bool $timestamps = false;

    protected ?string $table = 'export_download_logs';

    protected array $fillable = [
        'task_id',
        'user_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'task_id' => 'integer',
        'user_id' => 'integer',
        'downloaded_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ExportTask::class, 'task_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
