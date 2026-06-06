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

namespace App\Interface\Admin\Controller\DiyPage;

use App\Application\Admin\Content\AppDiyPublishCommandService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\DiyPage\DiyPublishRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/diy/pages')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DiyPublishController extends AbstractController
{
    public function __construct(
        private readonly AppDiyPublishCommandService $commandService,
        private readonly CurrentUser $currentUser,
    ) {}

    #[GetMapping(path: '{id:\d+}/publish-records')]
    #[Permission(code: 'mall:diy:page:publish')]
    public function records(int $id): Result
    {
        return $this->success($this->commandService->records($id));
    }

    #[PostMapping(path: '{id:\d+}/schedule-publish')]
    #[Permission(code: 'mall:diy:page:publish')]
    public function schedulePublish(int $id, DiyPublishRequest $request): Result
    {
        $record = $this->commandService->schedule($request->toScheduleDto($id), $this->currentUser->id());

        return $this->success($record->toArray(), '创建定时发布成功', 201);
    }

    #[PostMapping(path: '{id:\d+}/cancel-schedule')]
    #[Permission(code: 'mall:diy:page:publish')]
    public function cancelSchedule(int $id, DiyPublishRequest $request): Result
    {
        $params = $request->validated();
        $this->commandService->cancelSchedule((int) $params['record_id']);

        return $this->success([], '取消定时发布成功');
    }

    #[PostMapping(path: '{id:\d+}/rollback')]
    #[Permission(code: 'mall:diy:page:publish')]
    public function rollback(int $id, DiyPublishRequest $request): Result
    {
        $params = $request->validated();
        $version = $this->commandService->rollback($id, (int) $params['version_id'], $this->currentUser->id());

        return $this->success($version->toArray(), '回滚装修页面成功');
    }

    #[PostMapping(path: '{id:\d+}/preview-token')]
    #[Permission(code: 'mall:diy:page:read')]
    public function previewToken(int $id, DiyPublishRequest $request): Result
    {
        $params = $request->validated();
        $token = $this->commandService->createPreviewToken(
            $id,
            isset($params['version_id']) ? (int) $params['version_id'] : null,
            $this->currentUser->id()
        );

        return $this->success($token->toArray(), '生成预览令牌成功');
    }
}
