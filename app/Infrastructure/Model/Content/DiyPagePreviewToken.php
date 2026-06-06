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
 * @property string $token
 * @property Carbon $expired_at
 * @property null|int $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DiyPagePreviewToken extends Model
{
    protected ?string $table = 'diy_page_preview_tokens';

    protected array $fillable = [
        'page_id',
        'version_id',
        'token',
        'expired_at',
        'created_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'page_id' => 'integer',
        'version_id' => 'integer',
        'expired_at' => 'datetime',
        'created_by' => 'integer',
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
