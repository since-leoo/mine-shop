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

namespace App\Infrastructure\Exception\Auth\Handler;

use App\Infrastructure\Error\CommonError;
use App\Infrastructure\Exception\System\Handler\AbstractHandler;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Lcobucci\JWT\Exception;

final class JwtExceptionHandler extends AbstractHandler
{
    public function handleResponse(\Throwable $throwable): Result
    {
        $this->stopPropagation();
        return match (true) {
            $throwable->getMessage() === 'The token is expired' => new Result(
                code: ResultCode::UNAUTHORIZED,
                message: CommonError::TOKEN_EXPIRED->message(),
                errorCode: CommonError::TOKEN_EXPIRED->value,
            ),
            default => new Result(
                code: ResultCode::UNAUTHORIZED,
                message: CommonError::UNAUTHORIZED->message(),
                errorCode: CommonError::UNAUTHORIZED->value,
                data: $this->isDebug() ? [
                    'error' => $throwable->getMessage(),
                ] : []
            ),
        };
    }

    public function isValid(\Throwable $throwable): bool
    {
        return $throwable instanceof Exception;
    }
}
