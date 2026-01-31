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

use App\Application\Order\Assembler\OrderAssembler;
use App\Application\Order\Service\OrderCommandService;
use App\Interface\Api\Request\V1\OrderSubmitRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\RateLimit\Annotation\RateLimit;

#[Controller(prefix: '/api/v1/order')]
final class OrderController extends AbstractController
{
    public function __construct(private readonly OrderCommandService $commandService) {}

    #[PostMapping(path: 'preview')]
    #[RateLimit(create: 60, capacity: 20)]
    public function preview(OrderSubmitRequest $request): Result
    {
        $payload = $request->validated();
        $command = OrderAssembler::toSubmitCommand($payload);
        $draft = $this->commandService->preview($command);
        return $this->success($draft, '订单预览');
    }

    #[PostMapping(path: 'submit')]
    #[RateLimit(create: 30, capacity: 10)]
    public function submit(OrderSubmitRequest $request): Result
    {
        $payload = $request->validated();
        $command = OrderAssembler::toSubmitCommand($payload);
        $order = $this->commandService->submit($command);
        return $this->success([
            'order_no' => $order['order_no'] ?? null,
            'status' => $order['status'] ?? null,
            'order' => $order,
        ], '下单成功');
    }
}
