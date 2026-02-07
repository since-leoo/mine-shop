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

use App\Domain\Order\Contract\OrderPreviewInput;
use App\Domain\Order\Contract\OrderSubmitInput;
use App\Domain\Order\Event\OrderCreatedEvent;
use App\Domain\Order\Service\OrderService;
use App\Domain\SystemSetting\Service\MallSettingService;
use Hyperf\DbConnection\Db;

final class OrderCommandApiService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderCheckoutTransformer $transformer,
        private readonly MallSettingService $mallSettingService
    ) {}

    public function preview(OrderPreviewInput $input): array
    {
        $draft = $this->orderService->preview($input);

        return $this->transformer->transform($draft);
    }

    /**
     * @throws \Throwable
     */
    public function submit(OrderSubmitInput $input): array
    {
        $orderEntity = Db::transaction(fn () => $this->orderService->submit($input));
        // 订单创建成功
        event(new OrderCreatedEvent($orderEntity));

        return [
            'is_success' => (bool) $orderEntity->getOrderNo(),
            'trade_no' => $orderEntity->getOrderNo(),
            'transaction_id' => $orderEntity->getOrderNo(),
            'channel' => 'wechat',
            'pay_info' => '{}',
            'limit_goods_list' => null,
            'pay_methods' => $this->resolvePaymentMethods(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resolvePaymentMethods(): array
    {
        $payment = $this->mallSettingService->payment();

        return [
            [
                'channel' => 'wechat',
                'name' => '微信支付',
                'enabled' => $payment->wechatEnabled(),
            ],
            [
                'channel' => 'balance',
                'name' => '钱包',
                'enabled' => $payment->balanceEnabled(),
            ],
        ];
    }
}
