<?php

namespace Plugin\MineAdmin\Dictionary\Http\Controller;

use App\Http\Admin\Controller\AbstractController;
use App\Http\Admin\Middleware\PermissionMiddleware;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Middleware\OperationMiddleware;
use App\Http\Common\Result;
use App\Http\CurrentUser;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;
use Plugin\MineAdmin\Dictionary\Http\Request\DictionaryRequest as Request;
use Plugin\MineAdmin\Dictionary\Service\DictionaryService as Service;


#[OA\Tag('数据字典')]
#[OA\HyperfServer('http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class DictionaryController extends AbstractController
{
    public function __construct(
        private readonly Service $service,
        private readonly CurrentUser $currentUser
    ) {}

    #[Get(
        path: '/admin/data_center/dictionary/list',
        operationId: 'dataCenter:dictionary:list',
        summary: '数据字典列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['数据字典'],
    )]
    #[Permission(code: 'dataCenter:dictionary:list')]
    public function pageList(): Result
    {
        return $this->success(
            $this->service->page(
                $this->getRequestData(),
                $this->getCurrentPage(),
                $this->getPageSize()
            )
        );
    }

    #[Post(
        path: '/admin/data_center/dictionary',
        operationId: 'dataCenter:dictionary:save',
        summary: '新增数据字典',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['数据字典'],
    )]
    #[Permission(code: 'dataCenter:dictionary:save')]
    #[ResultResponse(instance: new Result())]
    public function create(Request $request): Result
    {
        $this->service->create(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[Put(
        path: '/admin/data_center/dictionary/{id}',
        operationId: 'dataCenter:dictionary:update',
        summary: '保存数据字典',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['数据字典'],
    )]
    #[Permission(code: 'dataCenter:dictionary:update')]
    #[ResultResponse(instance: new Result())]
    public function save(int $id, Request $request): Result
    {
        $this->service->updateById($id, array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[Delete(
        path: '/admin/data_center/dictionary',
        operationId: 'dataCenter:dictionary:delete',
        summary: '删除数据字典',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['数据字典'],
    )]
    #[ResultResponse(instance: new Result())]
    #[Permission(code: 'dataCenter:dictionary:delete')]
    public function delete(): Result
    {
        $this->service->deleteById($this->getRequestData());
        return $this->success();
    }

}
