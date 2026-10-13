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

namespace App\Domain\Infrastructure\Plugin\Contract;

use App\Domain\Trade\Review\Contract\ReviewInput;

interface ProductReviewCommandInterface
{
    public function create(int $memberId, ReviewInput $input): int;
}
