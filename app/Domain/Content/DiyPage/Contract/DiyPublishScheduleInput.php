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

namespace App\Domain\Content\DiyPage\Contract;

use Carbon\Carbon;

interface DiyPublishScheduleInput
{
    public function getPageId(): int;

    public function getVersionId(): int;

    public function getScheduledAt(): Carbon;

    public function getRemark(): ?string;
}
