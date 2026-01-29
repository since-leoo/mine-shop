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

namespace App\Infrastructure\Exception\System\Handler;

use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;

final class AppExceptionHandler extends AbstractHandler
{
    public function handleResponse(\Throwable $throwable): Result
    {
        $this->stopPropagation();
        return new Result(
            code: ResultCode::FAIL,
            message: $throwable->getMessage()
        );
    }

    public function isValid(\Throwable $throwable): bool
    {
        return true;
    }
}
