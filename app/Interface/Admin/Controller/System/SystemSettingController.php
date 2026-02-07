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

namespace App\Interface\Admin\Controller\System;

use App\Application\Commad\AppSystemSettingCommandService;
use App\Application\Query\AppSystemSettingQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\System\SystemSettingRequest;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/system/setting')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SystemSettingController extends AbstractController
{
    public function __construct(
        private readonly AppSystemSettingQueryService $queryService,
        private readonly AppSystemSettingCommandService $commandService
    ) {}

    #[GetMapping(path: 'groups')]
    #[Permission(code: 'system:setting:list')]
    public function groups(): Result
    {
        return $this->success($this->queryService->groups());
    }

    #[GetMapping(path: 'group/{group}')]
    #[Permission(code: 'system:setting:list')]
    public function group(string $group): Result
    {
        return $this->success($this->queryService->group($group));
    }

    #[PutMapping(path: '{key}')]
    #[Permission(code: 'system:setting:update')]
    public function update(string $key, SystemSettingRequest $request): Result
    {
        $payload = $request->validated();
        $value = $payload['value'] ?? null;
        $setting = $this->commandService->update($key, $value);
        return $this->success($setting, '配置已更新');
    }
}
