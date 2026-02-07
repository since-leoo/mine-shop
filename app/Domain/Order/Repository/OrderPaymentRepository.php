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

namespace App\Domain\Order\Repository;

use App\Infrastructure\Model\Order\OrderPayment;
use Hyperf\Collection\Collection;

class OrderPaymentRepository
{
    /**
     * 创建支付记录.
     */
    public function create(array $data): OrderPayment
    {
        return OrderPayment::query()->create($data);
    }

    /**
     * 通过 ID 查找.
     */
    public function findById(int $id): ?OrderPayment
    {
        return OrderPayment::query()->find($id);
    }

    /**
     * 通过支付单号查找.
     */
    public function findByPaymentNo(string $paymentNo): ?OrderPayment
    {
        return OrderPayment::query()->where('payment_no', $paymentNo)->first();
    }

    /**
     * 通过订单 ID 查找.
     */
    public function findByOrderId(int $orderId): ?OrderPayment
    {
        return OrderPayment::query()->where('order_id', $orderId)->first();
    }

    /**
     * 通过订单号查找.
     */
    public function findByOrderNo(string $orderNo): ?OrderPayment
    {
        return OrderPayment::query()->where('order_no', $orderNo)->first();
    }

    /**
     * 通过第三方支付单号查找.
     */
    public function findByThirdPartyNo(string $thirdPartyNo): ?OrderPayment
    {
        return OrderPayment::query()->where('third_party_no', $thirdPartyNo)->first();
    }

    /**
     * 获取会员的支付记录.
     */
    public function getByMemberId(int $memberId, int $limit = 20): Collection
    {
        return OrderPayment::query()
            ->where('member_id', $memberId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * 更新支付记录.
     */
    public function updateById(int $id, array $data): bool
    {
        return OrderPayment::query()->where('id', $id)->update($data) > 0;
    }

    /**
     * 更新支付记录（通过支付单号）.
     */
    public function updateByPaymentNo(string $paymentNo, array $data): bool
    {
        return OrderPayment::query()->where('payment_no', $paymentNo)->update($data) > 0;
    }

    /**
     * 删除支付记录.
     */
    public function deleteById(int $id): bool
    {
        return OrderPayment::query()->where('id', $id)->delete() > 0;
    }
}
