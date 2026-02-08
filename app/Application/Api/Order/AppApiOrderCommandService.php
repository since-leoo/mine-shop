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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Trade\Order\Api\Command\DomainApiOrderCommandService;
use App\Domain\Trade\Order\Contract\OrderPreviewInput;
use App\Domain\Trade\Order\Contract\OrderSubmitInput;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Event\OrderCreatedEvent;
use Hyperf\DbConnection\Db;

final class AppApiOrderCommandService
{
    public function __construct(
        private readonly DomainApiOrderCommandService $orderCommandService,
        private readonly DomainMallSettingService $mallSettingService
    ) {}

    /**
     * 预览订单，返回 OrderEntity.
     */
    public function preview(OrderPreviewInput $input): OrderEntity
    {
        return $this->orderCommandService->preview($input);
    }

    /**
     * 提交订单，返回 OrderEntity.
     *
     * @throws \Throwable
     */
    public function submit(OrderSubmitInput $input): OrderEntity
    {
        $orderEntity = Db::transaction(fn () => $this->orderCommandService->submit($input));
        event(new OrderCreatedEvent($orderEntity));

        return $orderEntity;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolvePaymentMethods(): array
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

    /**
     * 取消订单.
     */
    public function cancel(int $memberId, string $orderNo): void
    {
        $entity = $this->orderCommandService->getEntity(orderNo: $orderNo);

        if ($entity->getMemberId() !== $memberId) {
            throw new \RuntimeException('订单不存在');
        }

        $entity->cancel();

        Db::transaction(fn () => $this->orderCommandService->cancel($entity));
    }

    /**
     * 确认收货.
     */
    public function confirmReceipt(int $memberId, string $orderNo): void
    {
        $entity = $this->orderCommandService->getEntity(orderNo: $orderNo);

        if ($entity->getMemberId() !== $memberId) {
            throw new \RuntimeException('订单不存在');
        }

        Db::transaction(fn () => $this->orderCommandService->confirmReceipt($entity));
    }
}
