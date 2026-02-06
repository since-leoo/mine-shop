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

namespace App\Interface\Admin\Controller;

use App\Application\Commad\AttachmentCommandService;
use App\Application\Query\AttachmentQueryService;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\UploadRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Mine\Access\Attribute\Permission;
use Symfony\Component\Finder\SplFileInfo;

#[Controller(prefix: '/admin/attachment')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class AttachmentController extends AbstractController
{
    public function __construct(
        private readonly AttachmentQueryService $queryService,
        private readonly AttachmentCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'dataCenter:attachment:list')]
    public function list(UploadRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);

        isset($params['suffix']) && $params['suffix'] = explode(',', $params['suffix']);
        $params['current_user_id'] = $this->currentUser->id();

        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[PostMapping(path: 'upload')]
    #[Permission(code: 'dataCenter:attachment:upload')]
    public function upload(UploadRequest $request): Result
    {
        $uploadFile = $request->file('file');
        $newTmpPath = sys_get_temp_dir() . '/' . uniqid() . '.' . $uploadFile->getExtension();
        $uploadFile->moveTo($newTmpPath);
        $splFileInfo = new SplFileInfo($newTmpPath, '', '');

        $entity = $this->commandService->upload($splFileInfo, $uploadFile, $this->currentUser->id());
        return $this->success($entity->toResponse());
    }

    #[DeleteMapping(path: '{id}')]
    #[Permission(code: 'dataCenter:attachment:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success();
    }
}
