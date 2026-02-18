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

namespace Plugin\ExportCenter\Controller;

use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Plugin\ExportCenter\Service\ExportService;

#[Controller(prefix: '/admin/export')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly ExportService $exportService,
        private readonly CurrentUser $currentUser,
        private readonly RequestInterface $request
    ) {}

    /**
     * 查询导出任务列表.
     */
    #[GetMapping(path: 'tasks')]
    public function listTasks(): Result
    {
        $filters = [
            'status' => $this->request->input('status'),
            'dto_class' => $this->request->input('dto_class'),
            'start_date' => $this->request->input('start_date'),
            'end_date' => $this->request->input('end_date'),
        ];

        $page = (int) $this->request->input('page', 1);
        $pageSize = (int) $this->request->input('page_size', 20);

        $result = $this->exportService->getUserTasks(
            $this->currentUser->id(),
            $filters,
            $page,
            $pageSize
        );

        return $this->success($result);
    }

    /**
     * 查询任务详情.
     */
    #[GetMapping(path: 'tasks/{id}')]
    public function getTask(int $id): Result
    {
        $task = $this->exportService->getTaskDetail($id, $this->currentUser->id());
        return $this->success($task);
    }

    /**
     * 获取导出文件下载地址（第三方存储直链）.
     */
    #[GetMapping(path: 'tasks/{id}/download')]
    public function downloadFile(int $id): Result
    {
        $result = $this->exportService->getDownloadUrl($id, $this->currentUser->id());
        return $this->success($result);
    }

    /**
     * 删除导出任务
     */
    #[DeleteMapping(path: 'tasks/{id}')]
    public function deleteTask(int $id): Result
    {
        $this->exportService->deleteTask($id, $this->currentUser->id());
        return $this->success();
    }

    /**
     * 获取任务进度.
     */
    #[GetMapping(path: 'tasks/{id}/progress')]
    public function getProgress(int $id): Result
    {
        $progress = $this->exportService->getTaskProgress($id, $this->currentUser->id());
        return $this->success($progress);
    }
}
