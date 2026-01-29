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

namespace Plugin\MineAdmin\Dictionary\Http\Controller;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\Swagger\Annotation as OA;
use Hyperf\Swagger\Annotation\Delete;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;
use Plugin\MineAdmin\Dictionary\Http\Request\DictionaryTypeRequest as Request;
use Plugin\MineAdmin\Dictionary\Service\DictionaryTypeService as Service;

#[OA\Tag('字典分类')]
#[OA\HyperfServer('http')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class DictionaryTypeController extends AbstractController
{
    public function __construct(
        private readonly Service $service,
        private readonly CurrentUser $currentUser
    ) {}

    #[Get(
        path: '/admin/data_center/dictionary_type/getAllDictionary',
    )]
    public function getAllDictionary(): Result
    {
        return $this->success(
            $this->service->getAllDictionary()
        );
    }

    #[Get(
        path: '/admin/data_center/dictionary_type/list',
        operationId: 'dataCenter:dictionary:typeList',
        summary: '字典分类列表',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['字典分类'],
    )]
    #[Permission(code: 'dataCenter:dictionary:typeList')]
    public function pageList(): Result
    {
        return $this->success(
            $this->service->getList(
                $this->getRequestData()
            )
        );
    }

    #[Post(
        path: '/admin/data_center/dictionary_type',
        operationId: 'dataCenter:dictionary:typeSave',
        summary: '新增字典分类',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['字典分类'],
    )]
    #[Permission(code: 'dataCenter:dictionary:typeSave')]
    #[ResultResponse(instance: new Result())]
    public function create(Request $request): Result
    {
        $this->service->create(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[Put(
        path: '/admin/data_center/dictionary_type/{id}',
        operationId: 'dataCenter:dictionary:typeUpdate',
        summary: '保存字典分类',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['字典分类'],
    )]
    #[Permission(code: 'dataCenter:dictionary:typeUpdate')]
    #[ResultResponse(instance: new Result())]
    public function save(int $id, Request $request): Result
    {
        $this->service->updateById($id, array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[Delete(
        path: '/admin/data_center/dictionary_type',
        operationId: 'dataCenter:dictionary:typeDelete',
        summary: '删除字典分类',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['字典分类'],
    )]
    #[ResultResponse(instance: new Result())]
    #[Permission(code: 'dataCenter:dictionary:typeDelete')]
    public function delete(): Result
    {
        $this->service->deleteById($this->getRequestData());
        return $this->success();
    }
}
