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

use App\Application\Logstash\Assembler\LogQueryAssembler;
use App\Application\Logstash\Service\UserOperationLogService;
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

#[Controller(prefix: '/admin/user-operation-log')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
final class UserOperationLogController extends AbstractController
{
    public function __construct(
        protected readonly UserOperationLogService $service,
        protected readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'log:userOperation:list')]
    public function page(): Result
    {
        return $this->success($this->service->paginate(
            LogQueryAssembler::page(
                $this->getRequestData(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        ));
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'log:userOperation:delete')]
    public function delete(RequestInterface $request): Result
    {
        $this->service->delete($request->input('ids'));
        return $this->success();
    }
}
