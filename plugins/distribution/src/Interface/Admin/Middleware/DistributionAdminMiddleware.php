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

namespace Plugin\Distribution\Interface\Admin\Middleware;

use App\Infrastructure\Error\DistributionError;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\ResultCode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** Distribution administration is private to active super administrators, without menu records. */
final class DistributionAdminMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly CurrentUser $currentUser) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->currentUser->user();
        if ($user === null || ! $user->isSuperAdmin()) {
            throw new BusinessException(DistributionError::SUPER_ADMIN_REQUIRED, resultCode: ResultCode::FORBIDDEN);
        }
        if ($user->status->isDisable()) {
            throw new BusinessException(DistributionError::ADMIN_DISABLED, resultCode: ResultCode::DISABLED);
        }
        return $handler->handle($request);
    }
}
