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

use App\Application\Api\AfterSale\AppApiAfterSaleCommandService;
use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\AfterSale\AfterSaleApplyRequest;
use App\Interface\Api\Request\V1\AfterSale\AfterSaleReturnShipmentRequest;
use App\Interface\Api\Transformer\AfterSaleTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/after-sales')]
#[Middleware(TokenMiddleware::class)]
final class AfterSaleController extends AbstractController
{
    public function __construct(
        private readonly AppApiAfterSaleQueryService $queryService,
        private readonly AppApiAfterSaleCommandService $commandService,
        private readonly AfterSaleTransformer $transformer,
        private readonly CurrentMember $currentMember,
        private readonly RequestInterface $request,
    ) {}

    /**
     * æ¥è¯¢è®¢åååé¡¹æ¯å¦æ»¡è¶³ç³è¯·å®åçæ¡ä»¶ã
     */
    #[GetMapping(path: 'eligibility')]
    public function eligibility(): Result
    {
        $orderId = (int) $this->request->query('order_id', 0);
        $orderItemId = (int) $this->request->query('order_item_id', 0);

        return $this->success($this->queryService->eligibility($this->currentMember->id(), $orderId, $orderItemId));
    }

    /**
     * æäº¤å®åç³è¯·ã
     */
    #[PostMapping(path: '')]
    public function apply(AfterSaleApplyRequest $request): Result
    {
        $afterSale = $this->commandService->apply($request->toDto($this->currentMember->id()));

        return $this->successWithTransform($afterSale, fn ($item) => $this->transformer->transform($item), 'å®åç³è¯·å·²æäº¤');
    }

    /**
     * è·åå½åä¼åçå®åååè¡¨ã
     */
    #[GetMapping(path: '')]
    public function index(): Result
    {
        $status = (string) $this->request->query('status', 'all');
        $page = (int) $this->request->query('page', 1);
        $pageSize = (int) $this->request->query('page_size', 10);
        $paginator = $this->queryService->paginateByMember($this->currentMember->id(), $status, $page, $pageSize);

        return $this->successWithPaginator($paginator, fn ($item) => $this->transformer->transform($item));
    }

    /**
     * è·åå½åä¼åçå®ååè¯¦æã
     */
    #[GetMapping(path: '{id}')]
    public function detail(int $id): Result
    {
        $afterSale = $this->queryService->detail($this->currentMember->id(), $id);

        return $this->successWithTransform($afterSale, fn ($item) => $this->transformer->transform($item));
    }

    /**
     * æ¤éå½åä¼åèªå·±çå®ååã
     */
    #[PostMapping(path: '{id}/cancel')]
    public function cancel(int $id): Result
    {
        $this->commandService->cancel($this->currentMember->id(), $id);

        return $this->success([], 'å®ååå·²æ¤é');
    }

    /**
     * æäº¤ä¹°å®¶éè´§ç©æµã
     */
    #[PostMapping(path: '{id}/return-shipment')]
    public function submitReturnShipment(int $id, AfterSaleReturnShipmentRequest $request): Result
    {
        $this->commandService->submitReturnShipment($request->toDto($id, $this->currentMember->id()));

        return $this->success([], 'éè´§ç©æµå·²æäº¤');
    }
}
