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

namespace App\Domain\Trade\Order\Repository;

use App\Infrastructure\Abstract\IRepository;
use App\Infrastructure\Model\Order\OrderPayment;
use Hyperf\Collection\Collection;

/** @extends IRepository<OrderPayment> */
class OrderPaymentRepository extends IRepository
{
    public function __construct(protected readonly OrderPayment $model) {}

    /**
     * 通过支付单号查找.
     */
    public function findByPaymentNo(string $paymentNo): ?OrderPayment
    {
        /** @var null|OrderPayment $info */
        $info = $this->model::where('payment_no', $paymentNo)->first();

        return $info ?: null;
    }

    /**
     * 通过订单 ID 查找.
     */
    public function findByOrderId(int $orderId): ?OrderPayment
    {
        /** @var null|OrderPayment $info */
        $info = $this->model::where('order_id', $orderId)->first();

        return $info ?: null;
    }

    /**
     * 通过订单号查找.
     */
    public function findByOrderNo(string $orderNo): ?OrderPayment
    {
        /** @var null|OrderPayment $info */
        $info = $this->model::where('order_no', $orderNo)->first();

        return $info ?: null;
    }

    /**
     * 通过第三方支付单号查找.
     */
    public function findByThirdPartyNo(string $thirdPartyNo): ?OrderPayment
    {
        /** @var null|OrderPayment $info */
        $info = $this->model::where('third_party_no', $thirdPartyNo)->first();

        return $info ?: null;
    }

    /**
     * 获取会员的支付记录.
     * @return Collection<int, OrderPayment>
     */
    public function getByMemberId(int $memberId, int $limit = 20): Collection
    {
        return $this->model::where('member_id', $memberId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 更新支付记录.
     */
    public function updateById(int $id, array $data): bool
    {
        $record = $this->model::where('id', $id)->first();
        return $record !== null && $record->fill($data)->save();
    }

    /**
     * 更新支付记录（通过支付单号）.
     */
    public function updateByPaymentNo(string $paymentNo, array $data): bool
    {
        $record = $this->findByPaymentNo($paymentNo);
        return $record !== null && $record->fill($data)->save();
    }

    public function findForOrder(int $orderId, string $method): ?OrderPayment
    {
        return $this->model::where('order_id', $orderId)->where('payment_method', $method)->orderByDesc('id')->first();
    }

    public function findPaidByOrderIdForUpdate(int $orderId): ?OrderPayment
    {
        return $this->model::where('order_id', $orderId)->whereIn('status', ['paid', 'refunded'])
            ->orderByDesc('id')->lockForUpdate()->first();
    }

    /** @return Collection<int, OrderPayment> */
    public function findAllForOrderForUpdate(int $orderId): Collection
    {
        return $this->model::where('order_id', $orderId)->orderBy('id')->lockForUpdate()->get();
    }

    public function unreconciledWechat(int $limit = 100): array
    {
        return $this->model::where('payment_method', 'wechat')->where('status', 'pending')
            ->orderBy('updated_at')->limit($limit)->get()->all();
    }

    public function closePendingFromChannel(int $id, string $state): void
    {
        $status = match ($state) {
            'CLOSED', 'REVOKED' => 'cancelled',
            'PAYERROR' => 'failed',
            default => throw new \DomainException('支付渠道状态不是确定终态'),
        };
        // 条件更新防止查单结果覆盖已提交的成功回调。
        $this->model::where('id', $id)->where('status', 'pending')->update([
            'status' => $status, 'remark' => '渠道查单: ' . $state, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function touchReconciled(int $id): void
    {
        $this->model::where('id', $id)->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    public function sumBalancePaidToday(int $memberId): int
    {
        return (int) $this->model::where('member_id', $memberId)->where('payment_method', 'balance')
            ->whereNotNull('paid_at')->where('paid_at', '>=', \Carbon\Carbon::now()->startOfDay())
            ->sum('paid_amount');
    }
}
