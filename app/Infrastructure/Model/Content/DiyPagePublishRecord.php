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

namespace App\Infrastructure\Model\Content;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * @property int $id
 * @property int $page_id
 * @property null|int $version_id
 * @property string $publish_type
 * @property string $publish_status
 * @property null|Carbon $scheduled_at
 * @property null|Carbon $published_at
 * @property null|int $operator_id
 * @property null|string $remark
 * @property null|string $error_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DiyPagePublishRecord extends Model
{
    protected ?string $table = 'diy_page_publish_records';

    protected array $fillable = [
        'page_id',
        'version_id',
        'publish_type',
        'publish_status',
        'scheduled_at',
        'published_at',
        'operator_id',
        'remark',
        'error_message',
    ];

    protected array $casts = [
        'id' => 'integer',
        'page_id' => 'integer',
        'version_id' => 'integer',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'operator_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(DiyPage::class, 'page_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(DiyPageVersion::class, 'version_id');
    }
}
