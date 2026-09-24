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

namespace Plugin\Express\Contract;

use Plugin\Express\ValueObject\TrackingResult;

interface LogisticsTrackingInterface
{
    public function track(string $companyCode, string $trackingNo): TrackingResult;
}
