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
     * 提交订单（异步），返回 tradeNo + processing 状态.
     */
    public function submit(OrderSubmitInput $input): array
    {
        $entity = $this->orderCommandService->submit($input);

        return [
            'trade_no' => $entity->getOrderNo(),
            'status' => 'processing',
            'pay_methods' => $this->resolvePaymentMethods(),
        ];
    }

    /**
     * 查询异步下单结果.
     *
     * @return array{status: string, trade_no: string, error: string, pay_methods: array}
     */
    public function getSubmitResult(string $tradeNo): array
    {
        $result = $this->orderCommandService->getSubmitResult($tradeNo);

        return [
            'trade_no' => $tradeNo,
            'status' => $result['status'] ?: 'not_found',
            'error' => $result['error'],
            'pay_methods' => $result['status'] === 'created' ? $this->resolvePaymentMethods() : [],
        ];
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
