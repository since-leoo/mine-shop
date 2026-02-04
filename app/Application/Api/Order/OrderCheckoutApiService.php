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

namespace App\Application\Api\Order;

use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\Service\OrderService;

final class OrderCheckoutApiService
{
    public function __construct(
        private readonly OrderPayloadFactory $payloadFactory,
        private readonly OrderService $orderService,
        private readonly OrderCheckoutTransformer $transformer
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function preview(int $memberId, array $payload): array
    {
        $command = $this->payloadFactory->make($memberId, $payload);
        $draft = $this->orderService->preview($command);

        return $this->transformer->transform($draft);
    }

    /**
     * @param array<string, mixed> $payload
     * @throws \Throwable
     */
    public function submit(int $memberId, array $payload): array
    {
        $orderEntity = $this->orderService->submit($this->payloadFactory->make($memberId, $payload));
        // 订单创建成功
        event(new OrderCreatedEvent($orderEntity));

        return [
            'is_success' => (bool) $orderEntity->getOrderNo(),
            'trade_no' => $orderEntity->getOrderNo(),
            'transaction_id' => $orderEntity->getOrderNo(),
            'channel' => 'wechat',
            'pay_info' => '{}',
            'limit_goods_list' => null,
        ];
    }
}
