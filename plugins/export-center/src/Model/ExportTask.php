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
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $task_name
 * @property string $dto_class
 * @property string $export_format
 * @property array $export_params
 * @property string $status
 * @property null|int $progress
 * @property null|string $file_path
 * @property null|int $file_size
 * @property null|string $file_name
 * @property null|string $error_message
 * @property int $retry_count
 * @property null|Carbon $expired_at
 * @property null|Carbon $started_at
 * @property null|Carbon $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $deleted_at
 */
class ExportTask extends Model
{
    use SoftDeletes;

    protected ?string $table = 'export_tasks';

    protected array $fillable = [
        'user_id',
        'task_name',
        'dto_class',
        'export_format',
        'export_params',
        'status',
        'progress',
        'file_path',
        'file_size',
        'file_name',
        'error_message',
        'retry_count',
        'expired_at',
        'started_at',
        'completed_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'export_params' => 'array',
        'progress' => 'integer',
        'file_size' => 'integer',
        'retry_count' => 'integer',
        'expired_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function downloadLogs(): HasMany
    {
        return $this->hasMany(ExportDownloadLog::class, 'task_id', 'id');
    }
}
