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

namespace Plugin\WecomScrm\Model;

use Hyperf\DbConnection\Model\Model;

final class WecomCustomer extends Model
{
    protected ?string $table = 'wecom_customers';

    protected array $fillable = ['external_userid', 'name', 'avatar', 'type', 'gender', 'member_id', 'follow_userid', 'raw_data'];

    protected array $casts = ['raw_data' => 'array'];
}
