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
        private readonly DomainMallSettingService $mallSettingService,
        private readonly AppOrderSubmissionService $submissions,
        private readonly AppOrderCancellationService $cancellation,
        private readonly \App\Domain\Trade\Order\Contract\OrderSubmissionStoreInterface $submissionStore,
        private readonly \App\Domain\Trade\Shared\Contract\TradeOutboxInterface $outbox,
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
        $payload = $this->orderCommandService->prepareSubmission($input);
        $tradeNo = \App\Infrastructure\Model\Order\Order::generateOrderNo();
        $this->submissions->submit($tradeNo, $input->getMemberId(), $payload);

        return [
            'trade_no' => $tradeNo,
            'status' => 'processing',
            'pay_methods' => $this->resolvePaymentMethods(),
        ];
    }

    /**
     * 查询异步下单结果.
     *
     * @return array{status: string, trade_no: string, error: string, pay_methods: array}
     */
    public function getSubmitResult(string $tradeNo, int $memberId): array
    {
        $row = $this->submissionStore->find($tradeNo);
        if ($row === null || (int) $row['member_id'] !== $memberId) {
            throw new \DomainException('订单提交不存在');
        }
        $status = in_array($row['status'], ['pending', 'reserved'], true) ? 'processing' : $row['status'];
        $error = '';
        if ($row['status'] === 'failed') {
            $storedError = (string) ($row['last_error'] ?? '');
            // 只有新版失败处理明确标记的文案可公开；历史诊断保留在内部。
            $error = str_starts_with($storedError, AppOrderSubmissionService::PUBLIC_ERROR_PREFIX)
                ? substr($storedError, strlen(AppOrderSubmissionService::PUBLIC_ERROR_PREFIX))
                : AppOrderSubmissionService::GENERIC_FAILURE_MESSAGE;
        }

        return [
            'trade_no' => $tradeNo,
            'status' => $status ?: 'not_found',
            'error' => $error,
            'pay_methods' => $status === 'created' ? $this->resolvePaymentMethods() : [],
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
        $this->cancellation->cancel($orderNo, $memberId, reason: '用户取消订单');
    }

    /**
     * 确认收货.
     */
    public function confirmReceipt(int $memberId, string $orderNo): void
    {
        Db::transaction(function () use ($memberId, $orderNo): void {
            $entity = $this->orderCommandService->getLockedEntity($orderNo);
            if ($entity->getMemberId() !== $memberId) {
                throw new \DomainException('订单不存在');
            }
            $this->orderCommandService->confirmReceipt($entity);
            $this->outbox->append('completed:' . $orderNo, 'order.completed', [
                'order_id' => $entity->getId(), 'order_no' => $orderNo, 'member_id' => $memberId,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
        });
    }
}
