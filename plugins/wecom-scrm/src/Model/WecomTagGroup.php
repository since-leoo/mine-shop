<?php

declare(strict_types=1);

namespace Plugin\WecomScrm\Model;

use Hyperf\DbConnection\Model\Model;

final class WecomTagGroup extends Model
{
    protected ?string $table = 'wecom_tag_groups';
    protected array $fillable = ['group_id', 'name', 'is_editable'];
    protected array $casts = ['is_editable' => 'boolean'];
}
