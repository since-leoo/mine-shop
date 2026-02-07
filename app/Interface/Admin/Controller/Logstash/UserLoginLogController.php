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

namespace App\Interface\Admin\Controller\Logstash;

use App\Application\Commad\AppUserLoginLogCommandService;
use App\Application\Query\AppUserLoginLogQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/user-login-log')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
final class UserLoginLogController extends AbstractController
{
    public function __construct(
        protected readonly AppUserLoginLogQueryService $service,
        protected readonly AppUserLoginLogCommandService $commandService,
        protected readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'log:userLogin:list')]
    public function page(): Result
    {
        $params = $this->getRequestData();
        return $this->success(
            $this->service->page($params, $this->getCurrentPage(), $this->getPageSize())
        );
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'log:userLogin:delete')]
    public function delete(RequestInterface $request): Result
    {
        $this->commandService->delete($request->input('ids'));
        return $this->success();
    }
}
