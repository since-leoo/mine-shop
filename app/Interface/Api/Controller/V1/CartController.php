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

namespace App\Interface\Api\Controller\V1;

use App\Application\Api\Cart\CartCommandApiService;
use App\Application\Api\Cart\CartQueryApiService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\CartItemStoreRequest;
use App\Interface\Api\Request\V1\CartItemUpdateRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;

#[Controller(prefix: '/api/v1/cart')]
#[Middleware(TokenMiddleware::class)]
final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartQueryApiService $queryService,
        private readonly CartCommandApiService $commandService,
        private readonly CurrentMember $currentMember
    ) {}

    #[GetMapping(path: '')]
    public function index(): Result
    {
        $data = $this->queryService->overview($this->currentMember->id());
        return $this->success($data);
    }

    #[PostMapping(path: 'items')]
    public function store(CartItemStoreRequest $request): Result
    {
        $payload = $request->validated();
        $data = $this->commandService->addItem($this->currentMember->id(), $payload);

        return $this->success($data, '加入购物车成功');
    }

    #[PutMapping(path: 'items/{skuId}')]
    public function update(CartItemUpdateRequest $request, int $skuId): Result
    {
        $payload = $request->validated();
        $data = $this->commandService->updateItem($this->currentMember->id(), $skuId, $payload);

        return $this->success($data, '更新成功');
    }

    #[DeleteMapping(path: 'items/{skuId}')]
    public function destroy(int $skuId): Result
    {
        $data = $this->commandService->deleteItem($this->currentMember->id(), $skuId);
        return $this->success($data, '删除成功');
    }

    #[PostMapping(path: 'clear-invalid')]
    public function clearInvalid(): Result
    {
        $data = $this->commandService->clearInvalid($this->currentMember->id());
        return $this->success($data, '清理完成');
    }
}
