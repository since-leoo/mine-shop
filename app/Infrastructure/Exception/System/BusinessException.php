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

namespace App\Infrastructure\Exception\System;

use App\Infrastructure\Error\ErrorCode;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;

class BusinessException extends \RuntimeException
{
    private Result $response;

    public function __construct(
        ErrorCode|ResultCode $code = ResultCode::FAIL,
        ?string $message = null,
        mixed $data = [],
        array $parameters = [],
        ResultCode $resultCode = ResultCode::FAIL,
        ?int $errorCode = null,
    ) {
        $this->response = $code instanceof ErrorCode
            ? new Result($resultCode, $code->message($parameters), $data, (int) $code->value)
            : new Result($code, $message, $data, $errorCode);
        parent::__construct($this->response->message ?? '', $this->response->errorCode ?? $this->response->code->value);
    }

    public function getResponse(): Result
    {
        return $this->response;
    }
}
