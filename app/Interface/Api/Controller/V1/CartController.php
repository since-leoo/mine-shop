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

use App\Application\Api\Cart\AppApiCartCommandService;
use App\Application\Api\Cart\AppApiCartQueryService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\CartItemStoreRequest;
use App\Interface\Api\Request\V1\CartItemUpdateRequest;
use App\Interface\Api\Transformer\CartTransformer;
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
        private readonly AppApiCartQueryService $queryService,
        private readonly AppApiCartCommandService $commandService,
        private readonly CartTransformer $transformer,
        private readonly CurrentMember $currentMember
    ) {}

    #[GetMapping(path: '')]
    public function index(): Result
    {
        $memberId = $this->currentMember->id();
        $items = $this->queryService->listDetailed($memberId);
        return $this->success($this->transformer->transform($items, $memberId));
    }

    #[PostMapping(path: 'items')]
    public function store(CartItemStoreRequest $request): Result
    {
        $memberId = $this->currentMember->id();
        $items = $this->commandService->addItem($memberId, $request->toDto());
        return $this->success($this->transformer->transform($items, $memberId), '加入购物车成功');
    }

    #[PutMapping(path: 'items/{skuId}')]
    public function update(CartItemUpdateRequest $request, int $skuId): Result
    {
        $memberId = $this->currentMember->id();
        $items = $this->commandService->updateItem($memberId, $skuId, $request->toDto());
        return $this->success($this->transformer->transform($items, $memberId), '更新成功');
    }

    #[DeleteMapping(path: 'items/{skuId}')]
    public function destroy(int $skuId): Result
    {
        $memberId = $this->currentMember->id();
        $items = $this->commandService->deleteItem($memberId, $skuId);
        return $this->success($this->transformer->transform($items, $memberId), '删除成功');
    }

    #[PostMapping(path: 'clear-invalid')]
    public function clearInvalid(): Result
    {
        $memberId = $this->currentMember->id();
        $items = $this->commandService->clearInvalid($memberId);
        return $this->success($this->transformer->transform($items, $memberId), '清理完成');
    }
}
