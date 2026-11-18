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

use App\Infrastructure\Error\CommonError;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;

final class AppExceptionHandler extends AbstractHandler
{
    public function handleResponse(\Throwable $throwable): Result
    {
        $this->stopPropagation();
        $business = in_array($throwable::class, [\DomainException::class, \InvalidArgumentException::class], true);
        return new Result(
            code: ResultCode::FAIL,
            message: $this->isDebug() || $business
                ? $throwable->getMessage()
                : CommonError::SYSTEM_BUSY->message(),
            errorCode: $business ? ($throwable->getCode() ?: CommonError::BUSINESS_REJECTED->value) : CommonError::SYSTEM_BUSY->value,
        );
    }

    public function isValid(\Throwable $throwable): bool
    {
        return true;
    }
}
