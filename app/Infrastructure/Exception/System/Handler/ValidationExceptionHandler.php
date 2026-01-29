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
use Hyperf\Validation\ValidationException;

final class ValidationExceptionHandler extends AbstractHandler
{
    /**
     * @param ValidationException $throwable
     */
    public function handleResponse(\Throwable $throwable): Result
    {
        $this->stopPropagation();
        return new Result(
            code: ResultCode::UNPROCESSABLE_ENTITY,
            message: $throwable->validator->errors()->first()
        );
    }

    public function isValid(\Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
