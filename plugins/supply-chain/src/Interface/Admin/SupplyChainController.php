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

namespace Plugin\SupplyChain\Interface\Admin;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;
use Plugin\SupplyChain\Application\Commad\AppSupplyCommandService;
use Plugin\SupplyChain\Application\Query\AppSupplyQueryService;
use Plugin\SupplyChain\Interface\Request\AfterSaleSubmitRequest;
use Plugin\SupplyChain\Interface\Request\ConnectionRequest;
use Plugin\SupplyChain\Interface\Request\ImportRequest;
use Plugin\SupplyChain\Interface\Request\RetryTaskRequest;
use Plugin\SupplyChain\Interface\Request\SupplyQueryRequest;
use Plugin\SupplyChain\Interface\Transformer\SupplyTransformer;

#[Controller(prefix: '/admin/supply-chain')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SupplyChainController extends AbstractController
{
    public function __construct(
        private readonly AppSupplyQueryService $queries,
        private readonly AppSupplyCommandService $commands,
        private readonly SupplyTransformer $transformer,
        private readonly CurrentUser $currentUser,
    ) {}

    #[GetMapping(path: 'connections')]
    #[Permission(code: 'supply-chain:connections/read')]
    public function connections(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->connections($request->validated()), 'connection'));
    }

    #[PostMapping(path: 'connections')]
    #[Permission(code: 'supply-chain:connections/update')]
    public function createConnection(ConnectionRequest $request): Result
    {
        return $this->success($this->transformer->connection($this->commands->saveConnection(null, $request->toDto(), $this->currentUser->id())));
    }

    #[PutMapping(path: 'connections/{id:\d+}')]
    #[Permission(code: 'supply-chain:connections/update')]
    public function updateConnection(int $id, ConnectionRequest $request): Result
    {
        return $this->success($this->transformer->connection($this->commands->saveConnection($id, $request->toDto(), $this->currentUser->id())));
    }

    #[PostMapping(path: 'connections/{id:\d+}/test')]
    #[Permission(code: 'supply-chain:connections/test')]
    public function testConnection(int $id): Result
    {
        return $this->success($this->transformer->connectionTest($this->commands->testConnection($id)));
    }

    #[GetMapping(path: 'catalog')]
    #[Permission(code: 'supply-chain:catalog/read')]
    public function catalog(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->catalog($request->validated()), 'catalog'));
    }

    #[GetMapping(path: 'catalog/{externalId:.+}')]
    #[Permission(code: 'supply-chain:catalog/read')]
    public function catalogDetail(string $externalId, SupplyQueryRequest $request): Result
    {
        if ($externalId === '' || mb_strlen($externalId) > 128) {
            throw new \InvalidArgumentException('上游商品编号无效');
        }
        return $this->success($this->transformer->catalog($this->queries->catalogDetail((int) $request->validated()['connection_id'], $externalId)));
    }

    #[PostMapping(path: 'imports')]
    #[Permission(code: 'supply-chain:catalog/import')]
    public function importProducts(ImportRequest $request): Result
    {
        return $this->success($this->transformer->receipt($this->commands->importProducts($request->toDto(), $this->currentUser->id())));
    }

    #[GetMapping(path: 'products')]
    #[Permission(code: 'supply-chain:products/read')]
    public function products(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->products($request->validated()), 'product'));
    }

    #[PostMapping(path: 'products/{id:\d+}/sync')]
    #[Permission(code: 'supply-chain:products/sync')]
    public function syncProduct(int $id): Result
    {
        return $this->success($this->transformer->receipt($this->commands->sync($id, $this->currentUser->id())));
    }

    #[GetMapping(path: 'purchases')]
    #[Permission(code: 'supply-chain:purchases/read')]
    public function purchases(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->purchases($request->validated()), 'purchase'));
    }

    #[GetMapping(path: 'purchases/{id:\d+}')]
    #[Permission(code: 'supply-chain:purchases/read')]
    public function purchase(int $id): Result
    {
        return $this->success($this->transformer->purchase($this->queries->purchase($id)));
    }

    #[PostMapping(path: 'purchases/{id:\d+}/query')]
    #[Permission(code: 'supply-chain:purchases/query')]
    public function queryPurchase(int $id): Result
    {
        return $this->success($this->transformer->receipt($this->commands->queryPurchase($id, $this->currentUser->id())));
    }

    #[GetMapping(path: 'tasks')]
    #[Permission(code: 'supply-chain:tasks/read')]
    public function tasks(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->tasks($request->validated()), 'task'));
    }

    #[GetMapping(path: 'tasks/{id:\d+}')]
    #[Permission(code: 'supply-chain:tasks/read')]
    public function task(int $id): Result
    {
        return $this->success($this->transformer->task($this->queries->task($id)));
    }

    #[GetMapping(path: 'tasks/{id:\d+}/parameters')]
    #[Permission(code: ['supply-chain:tasks/read', 'supply-chain:logs/params'], operation: Permission::OPERATION_AND)]
    public function taskParameters(int $id): Result
    {
        return $this->success($this->transformer->task($this->queries->task($id), true));
    }

    #[PostMapping(path: 'tasks/{id:\d+}/retry')]
    #[Permission(code: 'supply-chain:tasks/retry')]
    public function retryTask(int $id, RetryTaskRequest $request): Result
    {
        return $this->success($this->transformer->receipt($this->commands->retry($id, $this->currentUser->id(), $request->reason())));
    }

    #[GetMapping(path: 'logs')]
    #[Permission(code: 'supply-chain:logs/read')]
    public function logs(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->logs($request->validated()), 'log'));
    }

    #[GetMapping(path: 'logs/{id:\d+}')]
    #[Permission(code: ['supply-chain:logs/read', 'supply-chain:logs/params'], operation: Permission::OPERATION_AND)]
    public function log(int $id): Result
    {
        return $this->success($this->transformer->log($this->queries->log($id), true));
    }

    #[GetMapping(path: 'after-sales')]
    #[Permission(code: 'supply-chain:after-sales/read')]
    public function afterSales(SupplyQueryRequest $request): Result
    {
        return $this->success($this->transformer->page($this->queries->afterSales($request->validated()), 'afterSale'));
    }

    #[GetMapping(path: 'after-sales/types')]
    #[Permission(code: ['supply-chain:after-sales/read', 'supply-chain:after-sales/submit'], operation: Permission::OPERATION_AND)]
    public function afterSaleTypes(SupplyQueryRequest $request): Result
    {
        $data = $request->validated();
        return $this->success($this->transformer->safe($this->queries->afterSaleTypes((int) $data['connection_id'], $data['external_order_item_id'])));
    }

    #[GetMapping(path: 'after-sales/{id:\d+}')]
    #[Permission(code: 'supply-chain:after-sales/read')]
    public function afterSale(int $id): Result
    {
        return $this->success($this->transformer->afterSale($this->queries->afterSale($id)));
    }

    #[PostMapping(path: 'after-sales/{id:\d+}/submit')]
    #[Permission(code: 'supply-chain:after-sales/submit')]
    public function submitAfterSale(int $id, AfterSaleSubmitRequest $request): Result
    {
        return $this->success($this->transformer->receipt($this->commands->submitAfterSale($id, $request->toDto()->toArray(), $this->currentUser->id())));
    }

    #[PostMapping(path: 'after-sales/{id:\d+}/query')]
    #[Permission(code: 'supply-chain:after-sales/query')]
    public function queryAfterSale(int $id): Result
    {
        return $this->success($this->transformer->receipt($this->commands->queryAfterSale($id, $this->currentUser->id())));
    }
}
