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

use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Lcobucci\JWT\Exception;

final class JwtExceptionHandler extends \App\Infrastructure\Exception\System\Handler\AbstractHandler
{
    public function handleResponse(\Throwable $throwable): Result
    {
        $this->stopPropagation();
        return match (true) {
            $throwable->getMessage() === 'The token is expired' => new Result(
                code: ResultCode::UNAUTHORIZED,
                message: trans('jwt.expired'),
            ),
            default => new Result(
                code: ResultCode::UNAUTHORIZED,
                message: trans('jwt.unauthorized'),
                data: [
                    'error' => $throwable->getMessage(),
                ]
            ),
        };
    }

    public function isValid(\Throwable $throwable): bool
    {
        return $throwable instanceof Exception;
    }
}
