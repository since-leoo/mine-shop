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

namespace App\Interface\Common\Controller;

use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;

abstract class AbstractController
{
    protected function success(mixed $data = [], ?string $message = null, mixed $_extra = null): Result
    {
        return new Result(ResultCode::SUCCESS, $message, $data);
    }

    protected function error(?string $message = null, mixed $data = []): Result
    {
        return new Result(ResultCode::FAIL, $message, $data);
    }

    protected function json(ResultCode $code, mixed $data = [], ?string $message = null): Result
    {
        return new Result($code, $message, $data);
    }
}
