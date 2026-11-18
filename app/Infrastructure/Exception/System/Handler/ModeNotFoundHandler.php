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
use Hyperf\Database\Model\ModelNotFoundException;

final class ModeNotFoundHandler extends AbstractHandler
{
    public function handleResponse(\Throwable $throwable): Result
    {
        $this->stopPropagation();
        return new Result(
            code: ResultCode::NOT_FOUND,
            message: CommonError::NOT_FOUND->message(),
            errorCode: CommonError::NOT_FOUND->value,
        );
    }

    public function isValid(\Throwable $throwable): bool
    {
        return $throwable instanceof ModelNotFoundException;
    }
}
