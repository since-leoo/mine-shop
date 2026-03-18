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

class OrderPaymentRepository extends IRepository
{
    public function __construct(protected readonly OrderPayment $model) {}

    /**
     * 通过支付单号查找.
     */
    public function findByPaymentNo(string $paymentNo): ?OrderPayment
    {
        /** @var OrderPayment $info */
        $info = $this->model::where('payment_no', $paymentNo)->first();

        return $info ?: null;
    }

    /**
     * 通过订单 ID 查找.
     */
    public function findByOrderId(int $orderId): ?OrderPayment
    {
        /** @var OrderPayment $info */
        $info = $this->model::where('order_id', $orderId)->first();

        return $info ?: null;
    }

    /**
     * 通过订单号查找.
     */
    public function findByOrderNo(string $orderNo): ?OrderPayment
    {
        /** @var OrderPayment $info */
        $info = $this->model::where('order_no', $orderNo)->first();

        return $info ?: null;
    }

    /**
     * 通过第三方支付单号查找.
     */
    public function findByThirdPartyNo(string $thirdPartyNo): ?OrderPayment
    {
        /** @var OrderPayment $info */
        $info = $this->model::where('third_party_no', $thirdPartyNo)->first();

        return $info ?: null;
    }

    /**
     * 获取会员的支付记录.
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
        return $this->model::where('id', $id)->update($data) > 0;
    }

    /**
     * 更新支付记录（通过支付单号）.
     */
    public function updateByPaymentNo(string $paymentNo, array $data): bool
    {
        return $this->model::where('payment_no', $paymentNo)->update($data) > 0;
    }
}
