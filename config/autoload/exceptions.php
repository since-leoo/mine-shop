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
use App\Infrastructure\Exception\System\Handler\AppExceptionHandler;
use App\Infrastructure\Exception\System\Handler\BusinessExceptionHandler;
use App\Infrastructure\Exception\Auth\Handler\JwtExceptionHandler;
use App\Infrastructure\Exception\System\Handler\ModeNotFoundHandler;
use App\Infrastructure\Exception\System\Handler\UnauthorizedExceptionHandler;
use App\Infrastructure\Exception\System\Handler\ValidationExceptionHandler;

return [
    'handler' => [
        'http' => [
            ModeNotFoundHandler::class,
            // 处理业务异常
            BusinessExceptionHandler::class,
            // 处理未授权异常
            UnauthorizedExceptionHandler::class,
            // 处理验证器异常
            ValidationExceptionHandler::class,
            // 处理JWT异常
            JwtExceptionHandler::class,
            // 处理应用异常
            AppExceptionHandler::class,
        ],
    ],
];
