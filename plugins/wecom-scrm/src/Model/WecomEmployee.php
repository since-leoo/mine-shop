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

final class WecomEmployee extends Model
{
    protected ?string $table = 'wecom_employees';

    protected array $fillable = ['wecom_userid', 'name', 'mobile', 'email', 'department_id', 'enable'];

    protected array $casts = ['enable' => 'boolean'];
}
